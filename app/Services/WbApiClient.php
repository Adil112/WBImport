<?php

namespace App\Services;

use App\Models\ApiToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WbApiClient
{
    public function getOrders(ApiToken $apiToken, string $dateFrom, string $dateTo, int $limit, int $page = 1, ?callable $onEvent = null): array
    {
        return $this->get($apiToken,
            '/api/orders',
            [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'page' => $page,
                'limit' => $limit,
            ],
            $onEvent,
        );
    }

    public function getSales(ApiToken $apiToken, string $dateFrom, string $dateTo, int $limit, int $page = 1, ?callable $onEvent = null): array
    {
        return $this->get($apiToken,
            '/api/sales',
            [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'page' => $page,
                'limit' => $limit,
            ],
            $onEvent,
        );
    }

    public function getIncomes(ApiToken $apiToken, string $dateFrom, string $dateTo, int $limit, int $page = 1, ?callable $onEvent = null): array
    {
        return $this->get($apiToken,
            '/api/incomes',
            [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'page' => $page,
                'limit' => $limit,
            ],
            $onEvent,
        );
    }

    public function getStocks(ApiToken $apiToken, string $dateFrom, int $limit, int $page = 1, ?callable $onEvent = null): array
    {
        return $this->get($apiToken,
            '/api/stocks',
            [
                'dateFrom' => $dateFrom,
                'page' => $page,
                'limit' => $limit,
            ],
            $onEvent,
        );
    }

    private function get(ApiToken $apiToken, string $endpoint, array $query, ?callable $onEvent = null): array
    {
        $credentials = $this->resolveQueryCredentials($apiToken);
        $baseUrl = rtrim(config('services.wb_api.base_url'), '/');
        $timeout = (int) config('services.wb_api.timeout', 30);
        $maxAttempts = (int) config('services.wb_api.retry_times', 5);
        $baseDelayMs = (int) config('services.wb_api.retry_delay_ms', 2000);

        $requestQuery = array_merge($query, [
            $credentials['parameter'] => $credentials['value'],
        ]);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($baseUrl . $endpoint, $requestQuery);

            if ($response->status() !== 429) {
                break;
            }

            if ($attempt === $maxAttempts) {
                throw new RuntimeException(
                    "API вернул 429 Too Many Requests. " . "Все {$maxAttempts} попыток исчерпаны."
                );
            }
            $delayMs = $this->resolveRetryDelay($response->header('Retry-After'), $baseDelayMs, $attempt);
            $event = [
                'type' => 'retry',
                'endpoint' => $endpoint,
                'account_id' => $apiToken->account_id,
                'token_id' => $apiToken->id,
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts,
                'delay_ms' => $delayMs,
                'status' => 429,
            ];
            Log::warning('Получен ответ 429 от API.', $event);
            if ($onEvent !== null) {
                $onEvent($event);
            }
            usleep($delayMs * 1000);
        }


        if (!$response->successful()) {
            throw new RuntimeException(
                'Запрос к API был провален. Статус запроса: ' . $response->status() . '. Тело запроса: ' . $response->body()
            );
        }
        $json = $response->json();
        if (!is_array($json)) {
            throw new RuntimeException('API вернул невалидный JSON');
        }
        $apiToken->update(['last_used_at' => now(),]);
        return $json;
    }

    private function resolveQueryCredentials(ApiToken $apiToken): array {
        $apiToken->loadMissing('tokenType');
        if ($apiToken->tokenType->slug !== 'query-key') {
            throw new RuntimeException(
                'WbApiClient поддерживает только тип токена query-key. '
                . "Передан тип: {$apiToken->tokenType->slug}."
            );
        }
        $credentials = $apiToken->credentials;
        $parameter = $credentials['parameter'] ?? null;
        $value = $credentials['value'] ?? null;

        if (!is_string($parameter) || trim($parameter) === '') {
            throw new RuntimeException('В credentials токена отсутствует название query-параметра.');
        }
        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException('В credentials токена отсутствует значение ключа.');
        }

        return [
            'parameter' => $parameter,
            'value' => $value,
        ];
    }

    private function resolveRetryDelay(?string $retryAfter, int $baseDelayMs, int $attempt): int
    {
        if ($retryAfter !== null && ctype_digit($retryAfter)) {
            return max(1, (int) $retryAfter) * 1000;
        }
        return $baseDelayMs * (2 ** ($attempt - 1));
    }
}
