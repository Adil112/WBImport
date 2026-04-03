<?php

namespace App\Services\Imports;

use App\Models\Stock;
use App\Services\Normalizers\StockNormalizer;
use App\Services\WbApiClient;

class StockImportService extends AbstractImportService
{
    private $client;
    private $normalizer;

    public function __construct(WbApiClient $client, StockNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
        $this->client = $client;
    }

    protected function fetchPage(string $dateFrom, string $dateTo, int $page, int $limit): array
    {
        return $this->client->getStocks($dateFrom, $limit, $page);
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
