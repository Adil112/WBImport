<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Console\Command;

class AccountAddCommand extends Command
{
    protected $signature = 'account:add
                            {name? : Название аккаунта}
                            {--company= : ID компании}
                            {--inactive : Создать аккаунт неактивным}';

    protected $description = 'Добавить новый аккаунт компании';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (Company::query()->doesntExist()) {
            $this->error('Сначала добавьте хотя бы одну компанию.');
            return self::FAILURE;
        }
        $company = $this->resolveCompany();
        if (!$company) {
            return self::FAILURE;
        }

        $name = $this->argument('name') ?: $this->ask('Введите название аккаунта');
        $name = trim((string) $name);
        if ($name === '') {
            $this->error('Название аккаунта не может быть пустым.');
            return self::FAILURE;
        }

        $accountExists = Account::query()
            ->where('company_id', $company->id)
            ->where('name', $name)
            ->exists();

        if ($accountExists) {
            $this->error(
                "Аккаунт «{$name}» уже существует "
                . "у компании «{$company->name}»."
            );
            return self::FAILURE;
        }

        $account = Account::query()->create([
            'company_id' => $company->id,
            'name' => $name,
            'is_active' => !$this->option('inactive'),
        ]);
        $this->newLine();
        $this->info('Аккаунт успешно добавлен.');
        $this->line("ID: {$account->id}");
        $this->line("Компания: {$company->name}");
        $this->line("Название аккаунта: {$account->name}");
        $this->line(
            'Статус: ' . ($account->is_active ? 'активен' : 'неактивен')
        );

        return self::SUCCESS;
    }

    private function resolveCompany(): ?Company
    {
        $companyOption = $this->option('company');
        if ($companyOption !== null) {
            $company = Company::query()->find($companyOption);
            if (!$company instanceof Company) {
                $this->error(
                    "Компания с ID {$companyOption} не найдена."
                );
                return null;
            }
            return $company;
        }

        $companies = Company::query()
            ->orderBy('name')
            ->get();

        $choices = $companies
            ->map(
                fn (Company $company) =>
                "{$company->name} (ID: {$company->id})"
            )
            ->values()
            ->all();

        $selectedLabel = $this->choice(
            'Выберите компанию',
            $choices
        );

        $selectedIndex = array_search(
            $selectedLabel,
            $choices,
            true
        );

        return $companies->get($selectedIndex);
    }
}
