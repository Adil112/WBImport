<?php

namespace App\Services\Imports;

use App\Models\Account;
use App\Models\ApiService;
use App\Models\ApiToken;
use App\Services\ApiTokenResolver;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

abstract class AbstractImportService
{
    public function __construct(protected readonly ApiTokenResolver $tokenResolver) {

    }
    public function import(Account $account, string $dateFrom, string $dateTo, ?int $limit = null, ?callable $onEvent = null): array
    {
        $apiService = $this->resolveApiService();
        $apiToken = $this->tokenResolver->resolve($account, $apiService,);

        $page = 1;
        $lastPage = 1;
        $processed = 0;
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $limit = $limit ?? config('services.wb_api.limit');

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

                $normalized = $this->normalize($item);
                $recordHash = $this->makeHash($normalized);

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

        return [
            'processed' => $processed,
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'last_page' => $lastPage,
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
    abstract protected function fetchPage(ApiToken $apiToken, string $dateFrom, string $dateTo, int $page, int $limit, ?callable $onEvent = null): array;

    abstract protected function normalize(array $item): array;

    abstract protected function makeHash(array $normalized): string;

    abstract protected function getModelClass(): string;
}
