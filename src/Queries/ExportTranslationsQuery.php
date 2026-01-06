<?php

namespace PhpJunior\Glosa\Queries;

use Illuminate\Support\Arr;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationValue;

class ExportTranslationsQuery
{
    public function get(string $localeCode, bool $nested = null)
    {
        $locale = Locale::where('code', $localeCode)->firstOrFail();

        $translations = TranslationValue::where('locale_id', $locale->id)
            ->with('translationKey')
            ->get()
            ->mapWithKeys(function ($item) {
                $key = $item->translationKey;
                $fullKey = $key->group === '*' ? $key->key : "{$key->group}.{$key->key}";
                return [$fullKey => $item->value];
            });

        $data = $translations->toArray();

        $shouldNest = $nested ?? config('glosa.public_api_nested', true);

        if ($shouldNest) {
            $data = Arr::undot($data);
        }

        return $data;
    }
}
