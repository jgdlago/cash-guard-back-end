<?php

namespace App\Models;

use App\Enums\CategoryDirection;
use App\Enums\CategoryKind;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'kind',
        'direction',
        'name',
        'slug',
        'color',
        'icon',
        'parent_id',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'kind' => CategoryKind::class,
            'direction' => CategoryDirection::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function recurringRules(): HasMany
    {
        return $this->hasMany(RecurringRule::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(CategoryUserPreference::class);
    }

    public function userPreference(): HasOne
    {
        return $this->hasOne(CategoryUserPreference::class);
    }
}
