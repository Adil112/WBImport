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

    protected function runImport(string $importType, string $importName, callable $import, bool $usesDateFrom = true, bool $usesDateTo = true): int
    {
        $dateFrom = $usesDateFrom ? $this->argument('dateFrom') : null;
        $dateTo = $usesDateTo ? $this->argument('dateTo') : null;
        $dateFrom = is_string($dateFrom) && $dateFrom !== '' ? $dateFrom : null;
        $dateTo = is_string($dateTo) && $dateTo !== '' ? $dateTo : null;

        $accountId = (int)$this->argument('account_id');
        $limit = (int)$this->option('limit');
        if ($limit < 1 || $limit > 500) {
            $this->error('Параметр --limit должен находиться в диапазоне от 1 до 500.');
            return self::FAILURE;
        }

        $logDateFrom = $dateFrom ?? now()->toDateString();
        $logDateTo = $dateTo ?? $logDateFrom;
        $log = $this->importLogger->start($importType, $logDateFrom, $logDateTo);

        try {
            $account = Account::findOrFail($accountId);

            $this->displayStartInformation(
                account: $account,
                importName: $importName,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                limit: $limit,
                usesDateFrom: $usesDateFrom,
                usesDateTo: $usesDateTo,
            );

            $result = $import($account, $dateFrom, $dateTo, $limit, $this->createImportEventHandler());

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

    private function displayStartInformation(Account $account, string $importName, ?string  $dateFrom, ?string $dateTo, int $limit, bool $usesDateFrom, bool $usesDateTo): void
    {
        $this->newLine();
        $this->info("Запущен импорт: {$importName}");
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Аккаунт', "{$account->name} (ID: {$account->id})"],
                [
                    'Дата начала', $usesDateFrom ? ($dateFrom ?? 'определяется автоматически') : 'сегодня',
                ],
                [
                    'Дата окончания', $usesDateTo ? ($dateTo ?? 'сегодня') : 'не используется',
                ],
                ['Лимит запроса', $limit],
            ],
        );
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
                ['Пропущено', $result['skipped']],
                ['Удалено неактуальных', $result['deleted'] ?? 0],
                ['Последняя страница', $result['last_page']],
            ],
        );
    }

    private function createImportEventHandler(): callable
    {
        return function (array $event): void {
            $type = $event['type'] ?? null;

            match ($type) {
                'date_range_resolved' => $this->displayResolvedDateRange($event),
                'page_started' => $this->displayPageStarted($event),
                'page_received' => $this->displayPageReceived($event),
                'page_processed' => $this->displayPageProcessed($event),
                'retry' => $this->displayRetry($event),
                default => null,
            };
        };
    }

    private function displayPageStarted(array $event): void
    {
        $this->newLine();
        $this->line(
            "Запрашивается страница {$event['page']}..."
        );
    }

    private function displayPageReceived(array $event): void
    {
        $this->line(
            "Страница {$event['page']} получена: "
            . "{$event['items_count']} записей. "
            . "Всего страниц: {$event['last_page']}."
        );
    }

    private function displayPageProcessed(array $event): void
    {
        $this->line(
            "Страница {$event['page']} обработана: "
            . "создано {$event['created']}, "
            . "обновлено {$event['updated']}, "
            . "без изменений {$event['unchanged']}."
        );
    }

    private function displayRetry(array $event): void
    {
        $delaySeconds = ((int) $event['delay_ms']) / 1000;
        $this->warn('Получен ответ 429 Too Many Requests.');
        $this->line(
            "Повторная попытка {$event['attempt']} "
            . "из {$event['max_attempts']} "
            . "через {$delaySeconds} сек."
        );
        $this->line(
            "Endpoint: {$event['endpoint']}; "
            . "аккаунт ID: {$event['account_id']}; "
            . "токен ID: {$event['token_id']}."
        );
    }

    private function displayResolvedDateRange(array $event): void
    {
        $this->newLine();
        $this->info('Определён фактический период импорта.');

        $this->table(
            ['Параметр', 'Значение'],
            [
                [
                    'Переданная дата начала', $event['requested_date_from'] ?? 'не передана',
                ],
                [
                    'Переданная дата окончания', $event['requested_date_to'] ?? 'не передана',
                ],
                [
                    'Последняя дата в БД', $event['last_stored_date'] ?? 'данных ещё нет',
                ],
                [
                    'Фактическая дата начала', $event['date_from'],
                ],
                [
                    'Фактическая дата окончания', $event['date_to'],
                ],
            ],
        );
    }
}
