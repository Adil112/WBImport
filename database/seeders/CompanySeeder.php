<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            [
                'name' => config(
                    'services.wb_api.seed_company_name',
                    'Test Company',
                ),
            ]
        );

        Account::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Main WB Account',
            ],
            ['is_active' => true]
        );
    }
}
