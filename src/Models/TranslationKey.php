<?php

namespace PhpJunior\Glosa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TranslationKey extends Model
{
    protected $table = 'glosa_keys';

    protected $guarded = [];

    public function values(): HasMany
    {
        return $this->hasMany(TranslationValue::class, 'key_id');
    }

    /**
     * Split a group such as 'client::labels' into [namespace, group].
     *
     * @return array{0: string, 1: string}
     */
    public static function parseGroup(string $group): array
    {
        if (str_contains($group, '::')) {
            [$namespace, $group] = explode('::', $group, 2);

            return [$namespace === '' ? '*' : $namespace, $group];
        }

        return ['*', $group];
    }

    /**
     * The group with its namespace prefix, e.g. 'client::labels'.
     */
    public function qualifiedGroup(): string
    {
        return in_array($this->namespace, [null, '*'], true) ? $this->group : "{$this->namespace}::{$this->group}";
    }

    /**
     * The key as passed to __(), e.g. 'client::labels.app_url'.
     */
    public function fullKey(): string
    {
        $key = $this->group === '*' ? $this->key : "{$this->group}.{$this->key}";

        return in_array($this->namespace, [null, '*'], true) ? $key : "{$this->namespace}::{$key}";
    }
}
