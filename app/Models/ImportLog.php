<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity',
        'date_from',
        'date_to',
        'status',
        'processed',
        'created',
        'updated',
        'unchanged',
        'last_page',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
