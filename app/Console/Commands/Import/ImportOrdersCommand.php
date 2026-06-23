<?php

namespace App\Console\Commands\Import;

use App\Models\Account;
use App\Services\Imports\ImportLogger;
use App\Services\Imports\OrderImportService;

class ImportOrdersCommand extends BaseImportCommand
{
    protected $signature = 'wb:import-orders
                            {account_id}
                            {dateFrom : Start date in Y-m-d format}
                            {dateTo : End date in Y-m-d format}
                            {--limit=500 : Items per page}';

    protected $description = 'Импорт заказов';

    public function __construct(private readonly OrderImportService $service, ImportLogger $importLogger)
    {
        parent::__construct($importLogger);
    }

    public function handle(): int
    {
        return $this->runImport(
            importType: 'orders',
            importName: 'заказы',
            import: fn (Account $account, string $dateFrom, ?string $dateTo, int $limit, callable $onEvent):
            array => $this->service->import($account, $dateFrom, $dateTo, $limit, $onEvent),
        );
    }
}
