<?php

namespace Database\Seeders;

use App\Models\ApiService;
use App\Models\TokenType;
use Illuminate\Database\Seeder;
use RuntimeException;

class ApiServiceSeeder extends Seeder
{
    public function run(): void
    {
        $apiService = ApiService::query()->updateOrCreate(
            ['slug' => 'wb-test'],
            [
                'name' => 'Wildberries API',
                'base_url' => config('services.wb_api.base_url'),
            ],
        );
        $queryKeyType = TokenType::query()
            ->where('slug', 'query-key')
            ->first();
        $apiService->tokenTypes()->syncWithoutDetaching([$queryKeyType->id]);
    }
}
