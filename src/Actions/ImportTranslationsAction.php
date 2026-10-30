<?php

namespace PhpJunior\Glosa\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;

class ImportTranslationsAction
{
    public function execute(string $localeCode, string $filePath, bool $overwrite = true): int
    {
        $locale = Locale::where('code', $localeCode)->firstOrFail();

        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new InvalidArgumentException('Translation file does not exist or is not readable.');
        }

        $content = file_get_contents($filePath);
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
            throw new InvalidArgumentException('Invalid JSON file');
        }

        $flattened = Arr::dot($json);

        return DB::transaction(function () use ($flattened, $locale, $overwrite) {
            $count = 0;

            foreach ($flattened as $key => $value) {
                if (is_array($value)) {
                    continue;
                }

                if (str_contains($key, '.')) {
                    $lastDot = strrpos($key, '.');
                    $group = substr($key, 0, $lastDot);
                    $keyName = substr($key, $lastDot + 1);
                } else {
                    $group = '*';
                    $keyName = $key;
                }

                // Namespaced keys are exported as 'namespace::group.key'.
                [$namespace, $group] = TranslationKey::parseGroup($group);

                $translationKey = TranslationKey::firstOrCreate([
                    'namespace' => $namespace,
                    'group' => $group,
                    'key' => $keyName,
                ]);

                if ($overwrite) {
                    $translationKey->values()->updateOrCreate(
                        [
                            'locale_id' => $locale->id,
                        ],
                        [
                            'value' => (string) $value,
                        ]
                    );
                } else {
                    $translationKey->values()->firstOrCreate(
                        [
                            'locale_id' => $locale->id,
                        ],
                        [
                            'value' => (string) $value,
                        ]
                    );
                }

                $count++;
            }

            return $count;
        });
    }
}
