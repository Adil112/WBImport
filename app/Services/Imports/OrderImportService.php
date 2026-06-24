<?php

namespace App\Services\Imports;

use App\Models\ApiToken;
use App\Models\Order;
use App\Services\ApiTokenResolver;
use App\Services\Normalizers\OrderNormalizer;
use App\Services\WbApiClient;

class OrderImportService extends AbstractImportService
{
    public function __construct(ApiTokenResolver $tokenResolver, private readonly WbApiClient $client, private readonly OrderNormalizer $normalizer) {
        parent::__construct($tokenResolver);
    }

    protected function fetchPage(ApiToken $apiToken, string $dateFrom, string $dateTo, int $page, int $limit, ?callable $onEvent = null): array
    {
        return $this->client->getOrders($apiToken, $dateFrom, $dateTo, $limit, $page, $onEvent);
    }

    protected function normalize(array $item): array
    {
        return $this->normalizer->normalize($item);
    }

    protected function makeHash(array $normalized): string
    {
        return $this->normalizer->makeHash($normalized);
    }

    protected function getModelClass(): string
    {
        return Order::class;
    }

    protected function getDateColumn(): string
    {
        return 'order_date';
    }
}
