<?php

namespace Database\Seeders;

use App\Models\TokenType;
use Illuminate\Database\Seeder;

class TokenTypeSeeder extends Seeder
{
    public function run(): void
    {
        $tokenTypes = [
            [
                'slug' => 'query-key',
                'name' => 'Query parameter key',
            ],
            [
                'slug' => 'bearer-token',
                'name' => 'Bearer token',
            ],
            [
                'slug' => 'api-key',
                'name' => 'API key header',
            ],
            [
                'slug' => 'login-password',
                'name' => 'Login and password',
            ],
        ];

        foreach ($tokenTypes as $tokenType) {
            TokenType::query()->updateOrCreate(
                ['slug' => $tokenType['slug']],
                ['name' => $tokenType['name']],
            );
        }
    }
}
