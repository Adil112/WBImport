<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ApiService;
use App\Models\ApiToken;
use App\Models\Company;
use App\Models\TokenType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class ImportIncomesRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_retries_after_429(): void
    {
        Http::fakeSequence()
            ->push(
                ['message' => 'Too Many Requests'],
                429,
                ['Retry-After' => '1']
            )
            ->push([
                'data' => [],
                'meta' => [
                    'last_page' => 1,
                ],
            ], 200);

        $company = Company::query()->create(['name' => 'Test Company',]);
        $account = Account::query()->create(['company_id' => $company->id, 'name' => 'Test Account', 'is_active' => true,]);
        $apiService = ApiService::query()->create(['name' => 'WB Test API', 'slug' => 'wb-test',]);
        $tokenType = TokenType::query()->create(['name' => 'Query key', 'slug' => 'query-key',]);
        $apiService->tokenTypes()->attach($tokenType->id);

        ApiToken::query()->create([
            'account_id' => $account->id,
            'api_service_id' => $apiService->id,
            'token_type_id' => $tokenType->id,
            'credentials' => [
                'parameter' => 'key',
                'value' => 'test-key',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);


        $this->artisan('wb:import-incomes', [
            'account_id' => $account->id,
            'dateFrom' => '2026-06-01',
            'dateTo' => '2026-06-23',
            '--limit' => 500,
        ])
            ->expectsOutput('Получен ответ 429 Too Many Requests.')
            ->assertExitCode(0);

        Http::assertSentCount(2);
    }
}
