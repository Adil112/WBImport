<?php

namespace App\Services\Imports;

use App\Models\Sale;
use App\Services\Normalizers\SaleNormalizer;
use App\Services\WbApiClient;

class SaleImportService extends AbstractImportService
{
    private $normalizer;
    private $client;

    public function __construct(WbApiClient $client, SaleNormalizer $normalizer)
    {
        $this->client = $client;
        $this->normalizer = $normalizer;
    }

    protected function fetchPage(string $dateFrom, string $dateTo, int $page, int $limit): array
    {
        return $this->client->getOrders($dateFrom, $dateTo, $limit, $page);
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
        return Sale::class;
    }
}
