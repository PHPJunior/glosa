<?php

namespace PhpJunior\Glosa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Locale extends Model
{
    protected $table = 'glosa_locales';

    protected $fillable = [
        'code',
        'name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * @return HasMany
     */
    public function values(): HasMany
    {
        return $this->hasMany(TranslationValue::class);
    }

    /**
     * @param int|null $exceptId
     * @return void
     */
    public static function resetDefault(?int $exceptId = null): void
    {
        $query = static::query();

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_default' => false]);
    }
}
