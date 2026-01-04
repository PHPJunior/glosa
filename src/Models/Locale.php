<?php

namespace PhpJunior\Glosa\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function values()
    {
        return $this->hasMany(TranslationValue::class);
    }
}
