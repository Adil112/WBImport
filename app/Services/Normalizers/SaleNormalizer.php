<?php

namespace App\Services\Normalizers;

use App\Services\Normalizers\NormalizesValues;

class SaleNormalizer
{
    use NormalizesValues;

    public function normalize(array $item): array
    {
        return [
            'g_number' => $this->toStringOrNull($item['g_number'] ?? null),
            'sale_date' => $this->toDateTimeOrNull($item['date'] ?? null),
            'last_change_date' => $this->toDateOrNull($item['last_change_date'] ?? null),
            'supplier_article' => $this->toStringOrNull($item['supplier_article'] ?? null),
            'tech_size' => $this->toStringOrNull($item['tech_size'] ?? null),
            'barcode' => $this->toIntOrNull($item['barcode'] ?? null),
            'total_price' => $this->toDecimalStringOrNull($item['total_price'] ?? null, 2),
            'discount_percent' => $this->toIntOrNull($item['discount_percent'] ?? null),
            'is_supply' => $this->toBoolOrNull($item['is_supply'] ?? null),
            'is_realization' => $this->toBoolOrNull($item['is_realization'] ?? null),
            'promo_code_discount' => $this->toIntOrNull($item['promo_code_discount'] ?? null),
            'warehouse_name' => $this->toStringOrNull($item['warehouse_name'] ?? null),
            'country_name' => $this->toStringOrNull($item['country_name'] ?? null),
            'oblast_okrug_name' => $this->toStringOrNull($item['oblast_okrug_name'] ?? null),
            'region_name' => $this->toStringOrNull($item['region_name'] ?? null),
            'income_id' => $this->toIntOrNull($item['income_id'] ?? null),
            'sale_id' => $this->toStringOrNull($item['sale_id'] ?? null),
            'odid' => $this->toStringOrNull($item['odid'] ?? null),
            'spp' => $this->toIntOrNull($item['spp'] ?? null),
            'for_pay' => $this->toDecimalStringOrNull($item['for_pay'] ?? null, 2),
            'finished_price' => $this->toDecimalStringOrNull($item['finished_price'] ?? null, 2),
            'price_with_disc' => $this->toDecimalStringOrNull($item['price_with_disc'] ?? null, 2),
            'nm_id' => $this->toIntOrNull($item['nm_id'] ?? null),
            'subject' => $this->toStringOrNull($item['subject'] ?? null),
            'category' => $this->toStringOrNull($item['category'] ?? null),
            'brand' => $this->toStringOrNull($item['brand'] ?? null),
            'is_storno' => $this->toBoolOrNull($item['is_storno'] ?? null),
        ];
    }

    public function makeHash(array $normalized): string
    {
        return $this->makeRecordHash($normalized);
    }
}
