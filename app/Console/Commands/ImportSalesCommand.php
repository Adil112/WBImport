<?php

namespace App\Console\Commands;

use App\Services\Imports\ImportLogger;
use App\Services\Imports\SaleImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportSalesCommand extends Command
{
    protected $signature = 'wb:import-sales
                            {dateFrom : Start date in Y-m-d format}
                            {dateTo : End date in Y-m-d format}
                            {--limit=500 : Items per page}';

    protected $description = 'Импорт продаж из WB API';
    private $service;
    private $importLogger;

    public function __construct(SaleImportService $service, ImportLogger $importLogger)
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
        $log = $this->importLogger->start('sales', $dateFrom, $dateTo);

        try {
            $this->info("Импортируем продажи с {$dateFrom} до {$dateTo}...");
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
