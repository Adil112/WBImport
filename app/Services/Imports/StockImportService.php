<?php

namespace App\Services\Imports;

use App\Models\Account;
use App\Models\ApiToken;
use App\Models\Stock;
use App\Services\ApiTokenResolver;
use App\Services\Normalizers\IncomeNormalizer;
use App\Services\Normalizers\StockNormalizer;
use App\Services\WbApiClient;

class StockImportService extends AbstractImportService
{

    public function __construct(ApiTokenResolver $tokenResolver, private readonly WbApiClient $client, private readonly StockNormalizer $normalizer) {
        parent::__construct($tokenResolver);
    }

    public function importCurrent(Account $account, ?int $limit = null, ?callable $onEvent = null,): array
    {
        $today = now()->toDateString();
        return parent::import($account, $today, $today, $limit, $onEvent);
    }

    protected function fetchPage(ApiToken $apiToken, string $dateFrom, string $dateTo, int $page, int $limit, ?callable $onEvent = null): array
    {
        return $this->client->getStocks($apiToken, $limit, $page, $onEvent);
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

    protected function getDateColumn(): string
    {
        return 'stock_date';
    }

    protected function finalizeImport(Account $account, array $importedRecordHashes): array
    {
        $query = Stock::query()->where('account_id', $account->id);
        if ($importedRecordHashes !== []) {
            $query->whereNotIn('record_hash', $importedRecordHashes);
        }
        $deleted = $query->delete();
        return ['deleted' => $deleted,];
    }
}
