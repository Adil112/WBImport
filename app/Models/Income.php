<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_hash',
        'income_id',
        'number',
        'income_date',
        'last_change_date',
        'supplier_article',
        'tech_size',
        'barcode',
        'quantity',
        'total_price',
        'date_close',
        'warehouse_name',
        'nm_id',
    ];

    protected $casts = [
        'income_date' => 'date',
        'last_change_date' => 'date',
        'date_close' => 'date',

        'quantity' => 'integer',
        'nm_id' => 'integer',
        'barcode' => 'integer',
        'total_price' => 'decimal:2',
    ];
}
