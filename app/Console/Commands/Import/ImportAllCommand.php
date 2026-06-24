<?php

namespace App\Console\Commands\Import;

use App\Models\Account;
use Illuminate\Console\Command;

class ImportAllCommand extends Command
{
    protected $signature = 'wb:import-all
                            {--account_id= : Импортировать только указанный аккаунт}
                            {--limit=500 : Количество записей на странице}';

    protected $description = 'Импортирует доходы, заказы, продажи и остатки для активных аккаунтов';

    public function handle(): int
    {
        $accountId = $this->option('account_id');
        $limit = (int) $this->option('limit');

        if ($limit < 1 || $limit > 500) {
            $this->error('Параметр --limit должен находиться в диапазоне от 1 до 500.');
            return self::FAILURE;
        }

        $query = Account::query()
            ->where('is_active', true)
            ->orderBy('id');

        if ($accountId !== null) {
            $query->whereKey((int) $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('Активные аккаунты для импорта не найдены.');
            return self::SUCCESS;
        }

        $commands = [
            'wb:import-incomes',
            'wb:import-orders',
            'wb:import-sales',
            'wb:import-stocks',
        ];

        $failedImports = [];

        foreach ($accounts as $account) {
            $this->newLine();
            $this->info("Начат импорт аккаунта #{$account->id}: {$account->name}");

            foreach ($commands as $command) {
                $this->newLine();
                $this->comment("Запуск команды: {$command}");

                $exitCode = $this->call($command, [
                    'account_id' => $account->id,
                    '--limit' => $limit,
                ]);

                if ($exitCode !== self::SUCCESS) {
                    $failedImports[] = [
                        'account_id' => $account->id,
                        'command' => $command,
                        'exit_code' => $exitCode,
                    ];
                    $this->error("Команда {$command} завершилась с кодом {$exitCode}.");
                }
            }

            $this->info("Импорт аккаунта #{$account->id} завершён.");
        }

        if ($failedImports !== []) {
            $this->newLine();
            $this->error('Некоторые операции импорта завершились ошибкой.');

            $this->table(['Аккаунт', 'Команда', 'Код'],
                array_map(
                    static fn (array $failure): array => [$failure['account_id'], $failure['command'], $failure['exit_code']],
                    $failedImports,
                ),
            );
            return self::FAILURE;
        }
        $this->newLine();
        $this->info('Импорт всех активных аккаунтов успешно завершён.');
        return self::SUCCESS;
    }
}
