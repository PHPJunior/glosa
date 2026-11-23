<?php

namespace PhpJunior\Glosa\Queries;

use Illuminate\Support\Arr;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;

class ExportTranslationsQuery
{
    public function get(string $localeCode, ?bool $nested = null)
    {
        $locale = Locale::where('code', $localeCode)->firstOrFail();
        $defaultLocale = Locale::where('is_default', true)->first();

        // Use TranslationKey as the base so keys without a value in the
        // exported locale are still included (falling back to the default locale).
        $data = TranslationKey::with([
            'values' => function ($query) use ($locale, $defaultLocale) {
                $query->whereIn('locale_id', array_unique(array_filter([
                    $locale->id,
                    $defaultLocale?->id,
                ])));
            },
        ])->get()->mapWithKeys(function (TranslationKey $key) use ($locale, $defaultLocale) {
            $value = $key->values->firstWhere('locale_id', $locale->id)?->value;

            if (($value === null || $value === '') && $defaultLocale) {
                $value = $key->values->firstWhere('locale_id', $defaultLocale->id)?->value;
            }

            return [$key->fullKey() => $value ?? ''];
        })->toArray();

        $shouldNest = $nested ?? config('glosa.public_api_nested', true);

        if ($shouldNest) {
            $data = Arr::undot($data);
        }

        return $data;
    }
}
