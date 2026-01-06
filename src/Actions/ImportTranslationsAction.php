<?php

namespace PhpJunior\Glosa\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;

class ImportTranslationsAction
{
    /**
     * @param string $localeCode
     * @param string $filePath
     * @return int
     */
    public function execute(string $localeCode, string $filePath): int
    {
        $locale = Locale::where('code', $localeCode)->firstOrFail();
        $json = json_decode(file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON file');
        }

        $flattened = Arr::dot($json);
        $count = 0;

        foreach ($flattened as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            if (strpos($key, '.') !== false) {
                $lastDot = strrpos($key, '.');
                $group = substr($key, 0, $lastDot);
                $keyName = substr($key, $lastDot + 1);
            } else {
                $group = '*';
                $keyName = $key;
            }

            $translationKey = TranslationKey::firstOrCreate([
                'group' => $group,
                'key' => $keyName,
            ]);

            TranslationValue::updateOrCreate(
                [
                    'key_id' => $translationKey->id,
                    'locale_id' => $locale->id,
                ],
                [
                    'value' => (string) $value
                ]
            );
            $count++;
        }

        return $count;
    }
}
