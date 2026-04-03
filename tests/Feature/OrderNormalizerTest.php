<?php

namespace Tests\Feature;

use App\Services\Normalizers\OrderNormalizer;
use Tests\TestCase;

class OrderNormalizerTest extends TestCase
{
    public function test_it_normalizes_order_correctly(): void
    {
        $normalizer = new OrderNormalizer();

        $input = [
            'g_number' => '123',
            'date' => '2026-03-01 12:00:00',
            'last_change_date' => '2026-03-01',
            'supplier_article' => 'abc',
            'tech_size' => 'size',
            'barcode' => 123456,
            'total_price' => '100.5',
            'discount_percent' => '10',
            'is_cancel' => 'false',
            'cancel_dt' => null,
        ];

        $result = $normalizer->normalize($input);

        $this->assertSame('123', $result['g_number']);
        $this->assertSame('2026-03-01 12:00:00', $result['order_date']);
        $this->assertSame('2026-03-01', $result['last_change_date']);
        $this->assertSame(123456, $result['barcode']);
        $this->assertSame('100.50', $result['total_price']);
        $this->assertSame(10, $result['discount_percent']);
        $this->assertFalse($result['is_cancel']);
    }
}
