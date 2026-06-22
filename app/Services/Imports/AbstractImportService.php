<?php

namespace App\Services\Imports;

use App\Models\Account;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

abstract class AbstractImportService
{
    public function import(Account $account, string $dateFrom, string $dateTo, ?int $limit = null): array
    {
        $page = 1;
        $lastPage = 1;
        $processed = 0;
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $limit = $limit ?? config('services.wb_api.limit');

        do {
            $response = $this->fetchPage($dateFrom, $dateTo, $page, $limit);

            $items = $response['data'] ?? null;
            $meta = $response['meta'] ?? [];

            if (! is_array($items)) {
                throw new RuntimeException('Данные ответа от API отсутствуют или неверны');
            }

            $lastPage = (int) ($meta['last_page'] ?? 1);

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
                } elseif ($model->wasChanged()) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            }

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

    abstract protected function fetchPage(string $dateFrom, string $dateTo, int $page, int $limit): array;

    abstract protected function normalize(array $item): array;

    abstract protected function makeHash(array $normalized): string;

    abstract protected function getModelClass(): string;
}
