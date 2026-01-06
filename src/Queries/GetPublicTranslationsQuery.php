<?php

namespace PhpJunior\Glosa\Queries;

use Illuminate\Support\Arr;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;

class GetPublicTranslationsQuery
{
    public function get(string $localeCode)
    {
        $localeModel = Locale::where('code', $localeCode)->firstOrFail();
        $defaultLocale = Locale::where('is_default', true)->first();

        // Get all translation keys with values for both requested and default locale
        // We use TranslationKey as the base to ensure we get all keys, even if the requested locale is missing some
        $translations = TranslationKey::with([
            'values' => function ($query) use ($localeModel, $defaultLocale) {
                $query->whereIn('locale_id', array_filter([
                    $localeModel->id,
                    $defaultLocale?->id
                ]));
            }
        ])->get()->mapWithKeys(function ($key) use ($localeModel, $defaultLocale) {
            $fullKey = $key->group === '*' ? $key->key : "{$key->group}.{$key->key}";

            // Find value for requested locale
            $value = $key->values->firstWhere('locale_id', $localeModel->id)?->value;

            // Fallback to default locale if value is empty or null
            if (empty($value) && $defaultLocale) {
                $value = $key->values->firstWhere('locale_id', $defaultLocale->id)?->value;
            }

            return [$fullKey => $value ?? ''];
        });

        if (config('glosa.public_api_nested', true)) {
            return Arr::undot($translations->toArray());
        }

        return $translations;
    }
}
