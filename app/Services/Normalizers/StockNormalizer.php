<?php

namespace App\Services\Normalizers;

use App\Services\Normalizers\NormalizesValues;

class StockNormalizer
{
    use NormalizesValues;

    public function normalize(array $item): array
    {
        return [
            'stock_date' => $this->toDateOrNull($item['date'] ?? null),
            'last_change_date' => $this->toDateOrNull($item['last_change_date'] ?? null),

            'supplier_article' => $this->toStringOrNull($item['supplier_article'] ?? null),
            'tech_size' => $this->toStringOrNull($item['tech_size'] ?? null),
            'barcode' => $this->toIntOrNull($item['barcode'] ?? null),

            'quantity' => $this->toIntOrNull($item['quantity'] ?? null),
            'is_supply' => $this->toBoolOrNull($item['is_supply'] ?? null),
            'is_realization' => $this->toBoolOrNull($item['is_realization'] ?? null),
            'quantity_full' => $this->toIntOrNull($item['quantity_full'] ?? null),

            'warehouse_name' => $this->toStringOrNull($item['warehouse_name'] ?? null),
            'in_way_to_client' => $this->toIntOrNull($item['in_way_to_client'] ?? null),
            'in_way_from_client' => $this->toIntOrNull($item['in_way_from_client'] ?? null),

            'nm_id' => $this->toIntOrNull($item['nm_id'] ?? null),

            'subject' => $this->toStringOrNull($item['subject'] ?? null),
            'category' => $this->toStringOrNull($item['category'] ?? null),
            'brand' => $this->toStringOrNull($item['brand'] ?? null),

            'sc_code' => $this->toIntOrNull($item['sc_code'] ?? null),

            'price' => $this->toDecimalStringOrNull($item['price'] ?? null, 2),
            'discount' => $this->toIntOrNull($item['discount'] ?? null),
        ];
    }

    public function makeHash(array $normalized): string
    {
        return $this->makeRecordHash($normalized);
    }
}
