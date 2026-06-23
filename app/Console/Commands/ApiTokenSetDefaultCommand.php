<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApiTokenSetDefaultCommand extends Command
{
    protected $signature = 'api-token:set-default
                            {token : ID токена}';

    protected $description = 'Назначить API-токен основным для аккаунта и API-сервиса';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tokenId = (int) $this->argument('token');
        $apiToken = ApiToken::query()
            ->with([
                'account.company',
                'apiService',
                'tokenType',
            ])
            ->find($tokenId);

        if (!$apiToken instanceof ApiToken) {
            $this->error("API-токен с ID {$tokenId} не найден.");
            return self::FAILURE;
        }

        if (!$apiToken->is_active) {
            $this->error(
                'Нельзя назначить основным неактивный токен.'
            );
            return self::FAILURE;
        }

        if ($apiToken->expires_at !== null && $apiToken->expires_at->isPast()) {
            $this->error('Нельзя назначить основным токен с истёкшим сроком действия.');
            return self::FAILURE;
        }

        if ($apiToken->is_default) {
            $this->info('Этот токен уже является основным.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($apiToken): void {
            ApiToken::query()
                ->where('account_id', $apiToken->account_id)
                ->where('api_service_id', $apiToken->api_service_id)
                ->where('is_default', true)
                ->update([
                    'is_default' => false,
                ]);

            $apiToken->update([
                'is_default' => true,
            ]);
        });

        $apiToken->refresh();

        $this->newLine();
        $this->info('Основной API-токен успешно изменён.');
        $this->line("ID токена: {$apiToken->id}");
        $this->line("Компания: {$apiToken->account->company->name}");
        $this->line("Аккаунт: {$apiToken->account->name}");
        $this->line("API-сервис: {$apiToken->apiService->name}");
        $this->line("Тип токена: {$apiToken->tokenType->name}");
        $this->line('Основной: да');

        return self::SUCCESS;
    }
}
