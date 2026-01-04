<?php

namespace PhpJunior\Glosa\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationKey extends Model
{
    protected $table = 'glosa_keys';
    protected $guarded = [];

    public function values()
    {
        return $this->hasMany(TranslationValue::class, 'key_id');
    }
}
