<?php

namespace App\Console\Commands\Import;

use App\Models\Account;
use App\Services\Imports\ImportLogger;
use App\Services\Imports\IncomeImportService;

class ImportIncomesCommand extends BaseImportCommand
{
    protected $signature = 'wb:import-incomes
                            {account_id}
                            {dateFrom : Start date in Y-m-d format}
                            {dateTo : End date in Y-m-d format}
                            {--limit=500 : Items per page}';

    protected $description = 'Импорт доходов';

    public function __construct(private readonly IncomeImportService $service, ImportLogger $importLogger)
    {
        parent::__construct($importLogger);
    }

    public function handle(): int
    {
        return $this->runImport(
            importType: 'incomes',
            importName: 'доходы',
            import: fn (Account $account, string $dateFrom, ?string $dateTo, int $limit, callable $onEvent):
            array => $this->service->import($account, $dateFrom, $dateTo, $limit, $onEvent),
        );
    }
}
