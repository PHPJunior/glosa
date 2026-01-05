<?php

namespace PhpJunior\Glosa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationValue extends Model
{
    protected $table = 'glosa_values';
    protected $guarded = [];

    /**
     * @return BelongsTo
     */
    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class);
    }

    /**
     * @return BelongsTo
     */
    public function translationKey(): BelongsTo
    {
        return $this->belongsTo(TranslationKey::class, 'key_id');
    }
}
