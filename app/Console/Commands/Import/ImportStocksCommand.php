<?php

namespace App\Console\Commands\Import;

use App\Models\Account;
use App\Services\Imports\ImportLogger;
use App\Services\Imports\StockImportService;

class ImportStocksCommand extends BaseImportCommand
{
    protected $signature = 'wb:import-stocks
                            {account_id}
                            {--limit=500 : Items per page}';

    protected $description = 'Импорт актуальных остатков';

    public function __construct(private readonly StockImportService $service, ImportLogger $importLogger)
    {
        parent::__construct($importLogger);
    }

    public function handle(): int
    {
        return $this->runImport(
            importType: 'stocks',
            importName: 'остатки',
            import: fn (Account $account, ?string $dateFrom, ?string $dateTo, int $limit, callable $onEvent):
            array => $this->service->importCurrent($account, $limit, $onEvent),
            usesDateFrom: false,
            usesDateTo: false
        );
    }
}
