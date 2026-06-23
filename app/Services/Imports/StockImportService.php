<?php

namespace App\Services\Imports;

use App\Models\ApiToken;
use App\Models\Stock;
use App\Services\ApiTokenResolver;
use App\Services\Normalizers\IncomeNormalizer;
use App\Services\Normalizers\StockNormalizer;
use App\Services\WbApiClient;

class StockImportService extends AbstractImportService
{

    public function __construct(ApiTokenResolver $tokenResolver, private readonly WbApiClient $client, private readonly IncomeNormalizer $normalizer) {
        parent::__construct($tokenResolver);
    }

    protected function fetchPage(ApiToken $apiToken, string $dateFrom, string $dateTo, int $page, int $limit, ?callable $onEvent = null): array
    {
        return $this->client->getStocks($apiToken, $dateFrom, $limit, $page, $onEvent);
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
        return Stock::class;
    }
}
