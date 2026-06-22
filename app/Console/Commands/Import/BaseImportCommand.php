<?php

namespace App\Console\Commands\Import;

use App\Models\Account;
use App\Services\Imports\ImportLogger;
use Illuminate\Console\Command;
use Throwable;

abstract class BaseImportCommand extends Command
{
    public function __construct(protected ImportLogger $importLogger,)
    {
        parent::__construct();
    }

    protected function runImport(string $importType, string $importName, callable $import, bool $usesDateTo = true): int
    {
        $accountId = (int)$this->argument('account_id');
        $dateFrom = (string)$this->argument('dateFrom');
        $dateTo = $usesDateTo ? (string)$this->argument('dateTo') : null;
        $limit = (int)$this->option('limit');

        $log = $this->importLogger->start($importType, $dateFrom, $dateTo ?? $dateFrom,);

        try {
            $account = Account::findOrFail($accountId);

            $this->displayStartInformation(
                account: $account,
                importName: $importName,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                limit: $limit,
            );

            $result = $import($account, $dateFrom, $dateTo, $limit);

            $this->importLogger->success($log, $result);
            $this->displayResult($result);
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->importLogger->failed($log, $exception);
            $this->newLine();
            $this->error('Импорт провален: ' . $exception->getMessage());
            report($exception);
            return self::FAILURE;
        }
    }

    private function displayStartInformation(Account $account, string $importName, string  $dateFrom, ?string $dateTo, int $limit,): void
    {
        $this->newLine();
        $this->info("Запущен импорт: {$importName}");
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Аккаунт', "{$account->name} (ID: {$account->id})"],
                ['Дата начала', $dateFrom],
                ['Дата окончания', $dateTo ?? 'не используется'],
                ['Лимит запроса', $limit],
            ],
        );
        $this->line('Получение данных...');
    }

    private function displayResult(array $result): void
    {
        $this->newLine();
        $this->info('Импорт успешно завершён.');
        $this->table(
            ['Показатель', 'Количество'],
            [
                ['Обработано', $result['processed']],
                ['Создано', $result['created']],
                ['Обновлено', $result['updated']],
                ['Без изменений', $result['unchanged']],
                ['Последняя страница', $result['last_page']],
            ],
        );
    }
}
