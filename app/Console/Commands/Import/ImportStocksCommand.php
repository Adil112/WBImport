<?php

namespace App\Console\Commands\Import;

use App\Models\Account;
use App\Services\Imports\ImportLogger;
use App\Services\Imports\StockImportService;

class ImportStocksCommand extends BaseImportCommand
{
    protected $signature = 'wb:import-stocks
                            {account_id}
                            {dateFrom : Start date in Y-m-d format}
                            {--limit=500 : Items per page}';

    protected $description = 'Импорт остатков';

    public function __construct(private readonly StockImportService $service, ImportLogger $importLogger)
    {
        parent::__construct($importLogger);
    }

    public function handle(): int
    {
        return $this->runImport(
            importType: 'stocks',
            importName: 'остатки',
            import: fn (Account $account, string $dateFrom, ?string $dateTo, int $limit, callable $onEvent):
            array => $this->service->import($account, $dateFrom, '', $limit, $onEvent),
            usesDateTo: false,
        );
    }
}
