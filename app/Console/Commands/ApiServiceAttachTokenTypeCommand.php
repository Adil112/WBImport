<?php

namespace App\Console\Commands;

use App\Models\ApiService;
use App\Models\TokenType;
use Illuminate\Console\Command;

class ApiServiceAttachTokenTypeCommand extends Command
{
    protected $signature = 'api-service:attach-token-type
                            {--service= : ID или slug API-сервиса}
                            {--token-type= : ID или slug типа токена}';

    protected $description = 'Связать API-сервис с допустимым типом токена';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (ApiService::query()->doesntExist()) {
            $this->error('Сначала добавьте хотя бы один API-сервис.');
            return self::FAILURE;
        }

        if (TokenType::query()->doesntExist()) {
            $this->error('Сначала добавьте хотя бы один тип токена.');
            return self::FAILURE;
        }

        $apiService = $this->resolveApiService();
        if (!$apiService) {
            return self::FAILURE;
        }

        $tokenType = $this->resolveTokenType();
        if (!$tokenType) {
            return self::FAILURE;
        }

        $alreadyAttached = $apiService
            ->tokenTypes()
            ->whereKey($tokenType->id)
            ->exists();

        if ($alreadyAttached) {
            $this->warn(
                "Тип токена «{$tokenType->name}» уже связан "
                . "с API-сервисом «{$apiService->name}»."
            );
            return self::SUCCESS;
        }

        $apiService->tokenTypes()->attach($tokenType->id);
        $this->newLine();
        $this->info('Тип токена успешно связан с API-сервисом.');
        $this->line("API-сервис: {$apiService->name}");
        $this->line("Slug сервиса: {$apiService->slug}");
        $this->line("Тип токена: {$tokenType->name}");
        $this->line("Slug типа: {$tokenType->slug}");

        return self::SUCCESS;
    }

    private function resolveApiService(): ?ApiService
    {
        $serviceOption = $this->option('service');

        if ($serviceOption !== null) {
            $apiService = ApiService::query()
                ->where('id', $serviceOption)
                ->orWhere('slug', $serviceOption)
                ->first();

            if (!$apiService instanceof ApiService) {
                $this->error(
                    "API-сервис «{$serviceOption}» не найден."
                );
                return null;
            }
            return $apiService;
        }

        $services = ApiService::query()
            ->orderBy('name')
            ->get();

        $choices = $services
            ->mapWithKeys(fn (ApiService $service) => [
                $service->id => "{$service->name} ({$service->slug})",
            ])
            ->all();

        $selectedLabel = $this->choice(
            'Выберите API-сервис',
            array_values($choices)
        );

        $selectedId = array_search(
            $selectedLabel,
            $choices,
            true
        );

        return $services->firstWhere('id', (int) $selectedId);
    }

    private function resolveTokenType(): ?TokenType
    {
        $tokenTypeOption = $this->option('token-type');

        if ($tokenTypeOption !== null) {
            $tokenType = TokenType::query()
                ->where('id', $tokenTypeOption)
                ->orWhere('slug', $tokenTypeOption)
                ->first();

            if (!$tokenType instanceof TokenType) {
                $this->error(
                    "Тип токена «{$tokenTypeOption}» не найден."
                );
                return null;
            }
            return $tokenType;
        }

        $tokenTypes = TokenType::query()
            ->orderBy('name')
            ->get();

        $choices = $tokenTypes
            ->mapWithKeys(fn (TokenType $tokenType) => [
                $tokenType->id => "{$tokenType->name} ({$tokenType->slug})",
            ])
            ->all();

        $selectedLabel = $this->choice(
            'Выберите тип токена',
            array_values($choices)
        );

        $selectedId = array_search(
            $selectedLabel,
            $choices,
            true
        );

        return $tokenTypes->firstWhere('id', (int) $selectedId);
    }
}
