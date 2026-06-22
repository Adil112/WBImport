<?php

namespace App\Console\Commands;

use App\Models\ApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ApiServiceAddCommand extends Command
{
    protected $signature = 'api-service:add
                            {name? : Название API-сервиса}
                            {--slug= : Уникальный идентификатор}
                            {--base-url= : URL API-сервиса}';
    protected $description = 'Добавить новый API-сервис';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name') ?: $this->ask('Введите название API-сервиса');
        $name = trim((string) $name);
        if ($name === '') {
            $this->error('Название API-сервиса не может быть пустым.');
            return self::FAILURE;
        }

        $slug = $this->option('slug');
        if (!$slug) {
            $defaultSlug = Str::slug($name);
            $slug = $this->ask(
                'Введите slug API-сервиса',
                $defaultSlug
            );
        }
        $slug = trim((string) $slug);
        if ($slug === '') {
            $this->error('Slug API-сервиса не может быть пустым.');
            return self::FAILURE;
        }
        if (ApiService::query()->where('slug', $slug)->exists()) {
            $this->error("API-сервис со slug «{$slug}» уже существует.");
            return self::FAILURE;
        }

        $baseUrl = $this->option('base-url');
        if (!$baseUrl) {
            $baseUrl = $this->ask('Введите URL API-сервиса');
        }
        $baseUrl = rtrim(trim((string) $baseUrl), '/');
        if ($baseUrl === '') {
            $this->error('URL не может быть пустым.');
            return self::FAILURE;
        }
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $this->error('Указан некорректный URL.');

            return self::FAILURE;
        }

        $apiService = ApiService::query()->create([
            'name' => $name,
            'slug' => $slug,
            'base_url' => $baseUrl,
        ]);
        $this->newLine();
        $this->info('API-сервис успешно добавлен.');
        $this->line("ID: {$apiService->id}");
        $this->line("Название: {$apiService->name}");
        $this->line("Slug: {$apiService->slug}");
        $this->line("Base URL: {$apiService->base_url}");

        return self::SUCCESS;
    }
}
