<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class WbApiClient
{
    public function getOrders(string $dateFrom, string $dateTo, int $limit, int $page = 1): array
    {
        return $this->get('/api/orders', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function getSales(string $dateFrom, string $dateTo, int $limit, int $page = 1): array
    {
        return $this->get('/api/sales', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function getIncomes(string $dateFrom, string $dateTo, int $limit, int $page = 1): array
    {
        return $this->get('/api/incomes', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function getStocks(string $dateFrom, int $limit, int $page = 1): array
    {
        return $this->get('/api/stocks', [
            'dateFrom' => $dateFrom,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    private function get(string $endpoint, array $query): array
    {
        $baseUrl = rtrim(config('services.wb_api.base_url'), '/');
        $key = config('services.wb_api.key');
        $timeout = (int) config('services.wb_api.timeout', 30);

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->get($baseUrl . $endpoint, array_merge($query, [
                'key' => $key,
            ]));

        if (!$response->successful()) {
            throw new RuntimeException(
                'Запрос к API был провален. Статус запроса: ' . $response->status() . '. Тело запроса: ' . $response->body()
            );
        }
        $json = $response->json();
        if (!is_array($json)) {
            throw new RuntimeException('API вернул невалидный JSON');
        }
        return $json;
    }
}
