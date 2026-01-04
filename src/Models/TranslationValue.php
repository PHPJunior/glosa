<?php

namespace PhpJunior\Glosa\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationValue extends Model
{
    protected $table = 'glosa_values';
    protected $guarded = [];

    public function locale()
    {
        return $this->belongsTo(Locale::class);
    }

    public function translationKey()
    {
        return $this->belongsTo(TranslationKey::class, 'key_id');
    }
}
