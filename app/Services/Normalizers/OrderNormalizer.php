<?php

namespace App\Services\Normalizers;

class OrderNormalizer
{
    use NormalizesValues;

    public function normalize(array $item): array
    {
        return [
            'g_number' => $this->toStringOrNull($item['g_number'] ?? null),
            'order_date' => $this->toDateTimeOrNull($item['date'] ?? null),
            'last_change_date' => $this->toDateOrNull($item['last_change_date'] ?? null),
            'supplier_article' => $this->toStringOrNull($item['supplier_article'] ?? null),
            'tech_size' => $this->toStringOrNull($item['tech_size'] ?? null),
            'barcode' => $this->toIntOrNull($item['barcode'] ?? null),
            'total_price' => $this->toDecimalStringOrNull($item['total_price'] ?? null, 2),
            'discount_percent' => $this->toIntOrNull($item['discount_percent'] ?? null),
            'warehouse_name' => $this->toStringOrNull($item['warehouse_name'] ?? null),
            'oblast' => $this->toStringOrNull($item['oblast'] ?? null),
            'income_id' => $this->toIntOrNull($item['income_id'] ?? null),
            'odid' => $this->toStringOrNull($item['odid'] ?? null),
            'nm_id' => $this->toIntOrNull($item['nm_id'] ?? null),
            'subject' => $this->toStringOrNull($item['subject'] ?? null),
            'category' => $this->toStringOrNull($item['category'] ?? null),
            'brand' => $this->toStringOrNull($item['brand'] ?? null),
            'is_cancel' => $this->toBoolOrNull($item['is_cancel'] ?? null),
            'cancel_dt' => $this->toDateOrNull($item['cancel_dt'] ?? null),
        ];
    }

    public function makeHash(array $normalized): string
    {
        return $this->makeRecordHash($normalized);
    }
}
