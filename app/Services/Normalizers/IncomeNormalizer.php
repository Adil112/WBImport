<?php

namespace App\Services\Normalizers;

use App\Services\Normalizers\NormalizesValues;

class IncomeNormalizer
{
    use NormalizesValues;

    public function normalize(array $item): array
    {
        return [
            'income_id' => $this->toIntOrNull($item['income_id'] ?? null),
            'number' => $this->toStringOrNull($item['number'] ?? null),
            'income_date' => $this->toDateOrNull($item['date'] ?? null),
            'last_change_date' => $this->toDateOrNull($item['last_change_date'] ?? null),
            'supplier_article' => $this->toStringOrNull($item['supplier_article'] ?? null),
            'tech_size' => $this->toStringOrNull($item['tech_size'] ?? null),
            'barcode' => $this->toIntOrNull($item['barcode'] ?? null),
            'quantity' => $this->toIntOrNull($item['quantity'] ?? null),
            'total_price' => $this->toDecimalStringOrNull($item['total_price'] ?? null, 2),
            'date_close' => $this->toDateOrNull($item['date_close'] ?? null),
            'warehouse_name' => $this->toStringOrNull($item['warehouse_name'] ?? null),
            'nm_id' => $this->toIntOrNull($item['nm_id'] ?? null),
        ];
    }

    public function makeHash(array $normalized): string
    {
        return $this->makeRecordHash($normalized);
    }
}
