<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class AddCompanyCommand extends Command
{
    protected $signature = 'company:add
                            {name? : Название компании}';

    protected $description = 'Добавить новую компанию';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        if (!$name) {
            $name = $this->ask('Введите название компании');
        }
        $name = trim($name);
        if ($name === '') {
            $this->error('Название компании не может быть пустым.');
            return self::FAILURE;
        }
        if (Company::query()->where('name', $name)->exists()) {
            $this->error("Компания с названием «{$name}» уже существует.");
            return self::FAILURE;
        }

        $company = Company::query()->create([
            'name' => $name,
        ]);
        $this->newLine();
        $this->info('Компания успешно добавлена.');
        $this->line("ID: {$company->id}");
        $this->line("Название: {$company->name}");

        return self::SUCCESS;
    }
}
