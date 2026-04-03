<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\Imports\OrderImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_orders(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    [
                        'g_number' => '123',
                        'date' => '2026-03-01 10:00:00',
                        'last_change_date' => '2026-03-01',
                        'supplier_article' => 'abc',
                        'tech_size' => 'size',
                        'barcode' => 123456,
                        'total_price' => '100',
                        'discount_percent' => 10,
                        'is_cancel' => false,
                        'cancel_dt' => null,
                    ]
                ],
                'meta' => [
                    'last_page' => 1,
                ]
            ], 200),
        ]);

        $service = app(OrderImportService::class);

        $result = $service->import('2026-03-01', '2026-03-01', 10);

        $this->assertEquals(1, $result['created']);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_import_is_idempotent(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    [
                        'g_number' => '123',
                        'date' => '2026-03-01 10:00:00',
                        'last_change_date' => '2026-03-01',
                        'supplier_article' => 'abc',
                        'tech_size' => 'size',
                        'barcode' => 123456,
                        'total_price' => '100',
                        'discount_percent' => 10,
                        'is_cancel' => false,
                        'cancel_dt' => null,
                    ]
                ],
                'meta' => [
                    'last_page' => 1,
                ]
            ], 200),
        ]);

        $service = app(OrderImportService::class);

        $service->import('2026-03-01', '2026-03-01', 10);
        $service->import('2026-03-01', '2026-03-01', 10);

        $this->assertDatabaseCount('orders', 1);
    }
}
