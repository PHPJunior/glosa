<?php

namespace PhpJunior\Glosa;

use Exception;
use Illuminate\Translation\FileLoader;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;

class TranslationLoader extends FileLoader
{
    /**
     * Load the messages for the given locale.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string|null  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null): array
    {
        $fileTranslations = parent::load($locale, $group, $namespace);

        if (!is_null($namespace) && $namespace !== '*') {
            return $fileTranslations;
        }

        $dbTranslations = $this->getDatabaseTranslations($locale, $group);

        return array_replace_recursive($fileTranslations, $dbTranslations);
    }

    /**
     * Fetch translations from the database.
     *
     * @param string $locale
     * @param string $group
     * @return array
     */
    protected function getDatabaseTranslations(string $locale, string $group): array
    {
        try {
            $localeModel = Locale::where('code', $locale)->first();

            if (!$localeModel) {
                return [];
            }

            $defaultLocale = Locale::where('is_default', true)->first();

            // Get all translation keys for the group
            $translationKeys = TranslationKey::where('group', $group)->get();

            // Load values for both locales in one query
            $values = TranslationValue::query()
                ->whereIn('locale_id', array_filter([
                    $localeModel->id,
                    $defaultLocale?->id
                ]))
                ->whereHas('translationKey', function ($query) use ($group) {
                    $query->where('group', $group);
                })
                ->with('translationKey')
                ->get();

            // Group values by translation key
            $valuesByKey = $values->groupBy('translation_key_id');

            return $translationKeys->mapWithKeys(function ($key) use ($localeModel, $defaultLocale, $valuesByKey) {
                $keyValues = $valuesByKey->get($key->id, collect());

                // Try requested locale first
                $value = $keyValues->firstWhere('locale_id', $localeModel->id)?->value;

                // Fallback to default locale if empty
                if (empty($value) && $defaultLocale) {
                    $value = $keyValues->firstWhere('locale_id', $defaultLocale->id)?->value;
                }

                return [$key->key => $value ?? ''];
            })->toArray();
        } catch (Exception $e) {
            return [];
        }
    }
}
