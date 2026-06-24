<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ApiService;
use App\Models\ApiToken;
use App\Models\TokenType;
use Illuminate\Database\Seeder;
use RuntimeException;

class ApiTokenSeeder extends Seeder
{
    public function run(): void
    {
        $apiKey = config('services.wb_api.key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            $this->command?->warn('WB_API_KEY не задан. API-токен не создан.');
            return;
        }

        $companyName = 'Test Company';
        $accountName = 'Main WB Account';
        $account = Account::query()
            ->where('name', $accountName)
            ->whereHas('company', static fn ($query) => $query->where('name', $companyName))
            ->first();
        $apiService = ApiService::query()
            ->where('slug', 'wb-test')
            ->first();
        $tokenType = TokenType::query()
            ->where('slug', 'query-key')
            ->first();

        ApiToken::query()->updateOrCreate(
            [
                'account_id' => $account->id,
                'api_service_id' => $apiService->id,
                'token_type_id' => $tokenType->id,
            ],
            [
                'credentials' => [
                    'parameter' => 'key',
                    'value' => trim($apiKey),
                ],
                'is_active' => true,
                'is_default' => true,
                'expires_at' => null,
            ],
        );
    }
}
