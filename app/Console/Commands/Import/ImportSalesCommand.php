<?php

namespace App\Console\Commands\Import;

use App\Models\Account;
use App\Services\Imports\ImportLogger;
use App\Services\Imports\SaleImportService;

class ImportSalesCommand extends BaseImportCommand
{
    protected $signature = 'wb:import-sales
                            {account_id}
                            {dateFrom? : Start date in Y-m-d format}
                            {dateTo? : End date in Y-m-d format}
                            {--limit=500 : Items per page}';

    protected $description = 'Импорт продаж';

    public function __construct(private readonly SaleImportService $service, ImportLogger $importLogger)
    {
        parent::__construct($importLogger);
    }

    public function handle(): int
    {
        return $this->runImport(
            importType: 'sales',
            importName: 'продажи',
            import: fn (Account $account, ?string $dateFrom, ?string $dateTo, int $limit, callable $onEvent):
            array => $this->service->import($account, $dateFrom, $dateTo, $limit, $onEvent),
        );
    }
}
