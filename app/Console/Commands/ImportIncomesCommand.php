<?php

namespace App\Console\Commands;

use App\Services\Imports\ImportLogger;
use App\Services\Imports\IncomeImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportIncomesCommand extends Command
{
    protected $signature = 'wb:import-incomes
                            {dateFrom : Start date in Y-m-d format}
                            {dateTo : End date in Y-m-d format}
                            {--limit=500 : Items per page}';

    protected $description = 'Импорт доходов из WB API';
    private $service;
    private $importLogger;

    public function __construct(IncomeImportService $service, ImportLogger $importLogger)
    {
        $this->importLogger = $importLogger;
        $this->service = $service;
        parent::__construct();
    }

    public function handle(): int
    {
        $dateFrom = $this->argument('dateFrom');
        $dateTo = $this->argument('dateTo');
        $limit = (int) $this->option('limit');
        $log = $this->importLogger->start('incomes', $dateFrom, $dateTo);

        try {
            $this->info("Импортируем доходы с {$dateFrom} до {$dateTo}...");
            $result = $this->service->import($dateFrom, $dateTo, $limit);
            $this->importLogger->success($log, $result);
            $this->info("Выполнено.
                Обработано записей: {$result['processed']},
                Создано записей: {$result['created']}.
                Обновлено записей: {$result['updated']}.
                Не тронуто записей: {$result['unchanged']}.
                Последняя страница: {$result['last_page']}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->importLogger->failed($log, $e);
            $this->error('Импорт провален: ' . $e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }
}
