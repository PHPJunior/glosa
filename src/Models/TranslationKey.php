<?php

namespace PhpJunior\Glosa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TranslationKey extends Model
{
    protected $table = 'glosa_keys';
    protected $guarded = [];

    /**
     * @return HasMany
     */
    public function values(): HasMany
    {
        return $this->hasMany(TranslationValue::class, 'key_id');
    }
}
