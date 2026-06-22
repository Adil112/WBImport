<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'base_url',
    ];

    public function tokenTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            TokenType::class,
            'api_service_token_types'
        );
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }
}
