<?php

namespace App\Services\Imports;

use App\Exceptions\SkipRecordException;
use App\Models\Account;
use App\Models\ApiService;
use App\Models\ApiToken;
use App\Services\ApiTokenResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use RuntimeException;

abstract class AbstractImportService
{
    public function __construct(protected readonly ApiTokenResolver $tokenResolver) {

    }
    public function import(Account $account, ?string $dateFrom, ?string $dateTo, ?int $limit = null, ?callable $onEvent = null): array
    {
        $range = $this->resolveDateRange($account, $dateFrom, $dateTo);
        $dateFrom = $range['date_from'];
        $dateTo = $range['date_to'];

        $this->dispatchEvent($onEvent, [
            'type' => 'date_range_resolved',
            'requested_date_from' => $range['requested_date_from'],
            'requested_date_to' => $range['requested_date_to'],
            'last_stored_date' => $range['last_stored_date'],
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        $apiService = $this->resolveApiService();
        $apiToken = $this->tokenResolver->resolve($account, $apiService,);

        $page = 1;
        $lastPage = 1;
        $processed = 0;
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $skipped = 0;
        $limit = $limit ?? config('services.wb_api.limit');
        $importedRecordHashes = [];

        do {
            $this->dispatchEvent($onEvent, [
                'type' => 'page_started',
                'page' => $page,
            ]);
            $response = $this->fetchPage($apiToken, $dateFrom, $dateTo, $page, $limit, $onEvent);

            $items = $response['data'] ?? null;
            $meta = $response['meta'] ?? [];

            if (! is_array($items)) {
                throw new RuntimeException('Данные ответа от API отсутствуют или неверны');
            }

            $lastPage = (int) ($meta['last_page'] ?? 1);

            $this->dispatchEvent($onEvent, [
                'type' => 'page_received',
                'page' => $page,
                'last_page' => $lastPage,
                'items_count' => count($items),
            ]);

            $pageCreated = 0;
            $pageUpdated = 0;
            $pageUnchanged = 0;

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                try {
                    $normalized = $this->normalize($item);
                    $recordHash = $this->makeHash($normalized);
                } catch (SkipRecordException $e) {
                    $skipped++;
                    Log::warning($e->getMessage(), ['item' => $item,]);
                    continue;
                }

                $importedRecordHashes[] = $recordHash;

                /** @var Model $model */
                $model = $this->getModelClass()::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'record_hash' => $recordHash,
                    ],
                    array_merge($normalized, [
                        'account_id' => $account->id,
                        'record_hash' => $recordHash,
                    ])
                );

                $processed++;

                if ($model->wasRecentlyCreated) {
                    $created++;
                    $pageCreated++;
                } elseif ($model->wasChanged()) {
                    $updated++;
                    $pageUpdated++;
                } else {
                    $unchanged++;
                    $pageUnchanged++;
                }
            }

            $this->dispatchEvent($onEvent, [
                'type' => 'page_processed',
                'page' => $page,
                'created' => $pageCreated,
                'updated' => $pageUpdated,
                'unchanged' => $pageUnchanged,
            ]);

            $page++;

            if ($page <= $lastPage) {
                usleep(((int) config('services.wb_api.sleep_ms', 1500)) * 1000);
            }
        } while ($page <= $lastPage);

        $finalizationResult = $this->finalizeImport($account, array_values(array_unique($importedRecordHashes)),);

        return [
            'processed' => $processed,
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'skipped' => $skipped,
            'deleted' => $finalizationResult['deleted'] ?? 0,
            'last_page' => $lastPage,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'last_stored_date' => $range['last_stored_date'],
        ];
    }
    private function resolveApiService(): ApiService
    {
        $slug = config('services.wb_api.service_slug');
        if (!is_string($slug) || $slug === '') {
            throw new RuntimeException('Не задан services.wb_api.service_slug.');
        }

        $apiService = ApiService::query()
            ->where('slug', $slug)
            ->first();

        if (!$apiService instanceof ApiService) {
            throw new RuntimeException("API-сервис со slug «{$slug}» не найден.");
        }

        return $apiService;
    }

    private function dispatchEvent(?callable $onEvent, array $event): void
    {
        if ($onEvent !== null) {
            $onEvent($event);
        }
    }

    private function resolveDateRange(Account $account, ?string $requestedDateFrom, ?string $requestedDateTo): array
    {
        $requestedDateFrom = $this->normalizeDate($requestedDateFrom);
        $requestedDateTo = $this->normalizeDate($requestedDateTo);

        $lastStoredDate = $this->getModelClass()::query()
            ->where('account_id', $account->id)
            ->whereNotNull($this->getDateColumn())
            ->max($this->getDateColumn());

        $lastStoredDate = $lastStoredDate !== null ? Carbon::parse($lastStoredDate)->toDateString() : null;
        $defaultDateFrom = config('services.wb_api.default_date_from');

        $dateFrom = match (true) {
            $requestedDateFrom !== null && $lastStoredDate !== null => max($requestedDateFrom, $lastStoredDate),
            $requestedDateFrom !== null => $requestedDateFrom,
            $lastStoredDate !== null => $lastStoredDate,
            default => Carbon::parse($defaultDateFrom)->toDateString(),
        };

        $dateTo = $requestedDateTo ?? now()->toDateString();

        if ($dateFrom > $dateTo) {
            throw new RuntimeException("Дата начала {$dateFrom} не может быть позже даты окончания {$dateTo}.");
        }

        return [
            'requested_date_from' => $requestedDateFrom,
            'requested_date_to' => $requestedDateTo,
            'last_stored_date' => $lastStoredDate,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    private function normalizeDate(?string $date): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat(
                'Y-m-d',
                $date,
            )->toDateString();
        } catch (\Throwable) {
            throw new RuntimeException(
                "Дата «{$date}» должна быть в формате Y-m-d."
            );
        }
    }

    protected function finalizeImport(Account $account, array $importedRecordHashes): array
    {
        return ['deleted' => 0];
    }
    abstract protected function fetchPage(ApiToken $apiToken, string $dateFrom, string $dateTo, int $page, int $limit, ?callable $onEvent = null): array;

    abstract protected function normalize(array $item): array;

    abstract protected function makeHash(array $normalized): string;

    abstract protected function getModelClass(): string;

    abstract protected function getDateColumn(): string;
}
