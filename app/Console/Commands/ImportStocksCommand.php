<?php

namespace App\Console\Commands;

use App\Services\Imports\ImportLogger;
use App\Services\Imports\StockImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportStocksCommand extends Command
{
    protected $signature = 'wb:import-stocks
                            {dateFrom : Start date in Y-m-d format}
                            {--limit=500 : Items per page}';

    protected $description = 'Импорт остатков из WB API';
    private $service;
    private $importLogger;

    public function __construct(StockImportService $service, ImportLogger $importLogger)
    {
        $this->importLogger = $importLogger;
        $this->service = $service;
        parent::__construct();
    }

    public function handle(): int
    {
        $dateFrom = $this->argument('dateFrom');
        $limit = (int) $this->option('limit');
        $log = $this->importLogger->start('stocks', $dateFrom, $dateFrom);

        try {
            $this->info("Импортируем остатки с {$dateFrom} ...");
            $result = $this->service->import($dateFrom, '', $limit);
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
