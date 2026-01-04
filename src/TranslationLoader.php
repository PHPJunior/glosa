<?php

namespace PhpJunior\Glosa;

use Illuminate\Translation\FileLoader;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationValue;
use Illuminate\Support\Facades\Schema;

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
    public function load($locale, $group, $namespace = null)
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
    protected function getDatabaseTranslations($locale, $group)
    {
        try {
            $localeModel = Locale::where('code', $locale)->first();

            if (!$localeModel) {
                return [];
            }

            return TranslationValue::query()
                ->where('locale_id', $localeModel->id)
                ->whereHas('translationKey', function ($query) use ($group) {
                    $query->where('group', $group);
                })
                ->with('translationKey')
                ->get()
                ->mapWithKeys(function ($value) {
                    return [$value->translationKey->key => $value->value];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}
