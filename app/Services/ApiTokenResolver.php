<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ApiService;
use App\Models\ApiToken;
use RuntimeException;

class ApiTokenResolver
{
    public function resolve(Account $account, ApiService $apiService): ApiToken
    {
        $apiToken = ApiToken::query()
            ->with(['apiService', 'tokenType',])
            ->where('account_id', $account->id)
            ->where('api_service_id', $apiService->id)
            ->where('is_active', true)
            ->where('is_default', true)
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$apiToken instanceof ApiToken) {
            throw new RuntimeException(
                "Для аккаунта «{$account->name}» "
                . "не найден основной активный токен "
                . "для API-сервиса «{$apiService->name}»."
            );
        }
        return $apiToken;
    }
}
