<?php

namespace App\Services\Imports;

use App\Models\Income;
use App\Services\Normalizers\IncomeNormalizer;
use App\Services\WbApiClient;

class IncomeImportService extends AbstractImportService
{
    private $normalizer;
    private $client;

    public function __construct(WbApiClient $client, IncomeNormalizer $normalizer)
    {
        $this->client = $client;
        $this->normalizer = $normalizer;
    }

    protected function fetchPage(string $dateFrom, string $dateTo, int $page, int $limit): array
    {
        return $this->client->getIncomes($dateFrom, $dateTo, $limit, $page);
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
        return Income::class;
    }
}
