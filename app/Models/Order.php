<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_hash',
        'g_number',
        'order_date',
        'last_change_date',
        'supplier_article',
        'tech_size',
        'barcode',
        'total_price',
        'discount_percent',
        'warehouse_name',
        'oblast',
        'income_id',
        'odid',
        'nm_id',
        'subject',
        'category',
        'brand',
        'is_cancel',
        'cancel_dt',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'last_change_date' => 'date',
        'cancel_dt' => 'date',

        'is_cancel' => 'boolean',

        'total_price' => 'decimal:2',
        'barcode' => 'integer',
        'discount_percent' => 'integer',
        'income_id' => 'integer',
        'nm_id' => 'integer',
    ];
}
