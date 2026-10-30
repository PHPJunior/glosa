<?php

namespace PhpJunior\Glosa\Actions;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;

class ImportLangDirectoryAction
{
    protected const CHUNK_SIZE = 500;

    public function __construct(protected Filesystem $files) {}

    /**
     * Import a Laravel lang directory into the database.
     *
     * PHP files ({locale}/{group}.php) are stored with the file name as the group
     * and the nested array path as the key, so __('group.nested.key') resolves.
     * JSON files ({locale}.json) are stored in the '*' group with keys as-is.
     * PHP files are stored under $namespace, so a module registered with
     * loadTranslationsFrom($path, 'client') resolves __('client::group.key').
     * Package overrides in vendor/{namespace}/{locale}/{group}.php keep their namespace.
     *
     * @param string $path
     * @param array<int, string> $onlyLocales
     * @param bool $overwrite
     * @param string $namespace
     * @return array<string, array{found: int, written: int}> Stats keyed by locale code.
     * @throws \Throwable
     */
    public function execute(string $path, array $onlyLocales = [], bool $overwrite = false, string $namespace = '*'): array
    {
        if (! $this->files->isDirectory($path)) {
            throw new InvalidArgumentException("Directory [{$path}] does not exist.");
        }

        $translations = $this->collect($path, $namespace);

        if ($onlyLocales !== []) {
            $translations = array_intersect_key($translations, array_flip($onlyLocales));
        }

        $stats = [];

        foreach ($translations as $localeCode => $namespaces) {
            $stats[$localeCode] = DB::transaction(
                fn () => $this->importLocale((string) $localeCode, $namespaces, $overwrite)
            );
        }

        return $stats;
    }

    /**
     * Read every translation file into [locale => [namespace => [group => [key => value]]]].
     */
    protected function collect(string $path, string $namespace): array
    {
        $translations = [];

        $this->collectPhpFiles($translations, $path, $namespace);

        foreach ($this->files->glob($path.'/vendor/*', GLOB_ONLYDIR) as $packageDirectory) {
            $this->collectPhpFiles($translations, $packageDirectory, basename($packageDirectory));
        }

        foreach ($this->files->glob($path.'/*.json') as $file) {
            $lines = json_decode($this->files->get($file), true);

            if (! is_array($lines)) {
                throw new InvalidArgumentException("Invalid JSON in [{$file}].");
            }

            $translations[basename($file, '.json')]['*']['*'] = $this->scalarLines($lines);
        }

        return $translations;
    }

    /**
     * Read {locale}/{group}.php files under $path into $translations.
     */
    protected function collectPhpFiles(array &$translations, string $path, string $namespace): void
    {
        foreach ($this->files->directories($path) as $directory) {
            $locale = basename($directory);

            if ($locale === 'vendor') {
                continue;
            }

            foreach ($this->files->allFiles($directory) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $group = str_replace('\\', '/', substr($file->getRelativePathname(), 0, -4));
                $lines = $this->files->getRequire($file->getPathname());

                if (is_array($lines)) {
                    $translations[$locale][$namespace][$group] = $this->scalarLines(Arr::dot($lines));
                }
            }
        }
    }

    /**
     * Keep only lines that can be stored as a string value.
     */
    protected function scalarLines(array $lines): array
    {
        return array_map('strval', array_filter($lines, 'is_scalar'));
    }

    protected function importLocale(string $localeCode, array $namespaces, bool $overwrite): array
    {
        $locale = Locale::firstOrCreate(
            ['code' => $localeCode],
            [
                'name' => $localeCode,
                'is_default' => $localeCode === config('app.locale')
                    && ! Locale::where('is_default', true)->exists(),
            ]
        );

        $stats = ['found' => 0, 'written' => 0];

        foreach ($namespaces as $namespace => $groups) {
            foreach ($groups as $group => $lines) {
                if ($lines !== []) {
                    $written = $this->importGroup($locale, (string) $namespace, (string) $group, $lines, $overwrite);

                    $stats['found'] += count($lines);
                    $stats['written'] += $written;
                }
            }
        }

        return $stats;
    }

    /**
     * Store one group's lines for the locale and return the number of values written.
     */
    protected function importGroup(Locale $locale, string $namespace, string $group, array $lines, bool $overwrite): int
    {
        $now = now();
        $keyIds = $this->ensureKeys($namespace, $group, array_keys($lines), $now);

        $rows = [];
        foreach ($lines as $key => $value) {
            // Keys differing only by case collapse into one row on case-insensitive collations.
            if (! isset($keyIds[$key])) {
                continue;
            }

            $rows[] = [
                'key_id' => $keyIds[$key],
                'locale_id' => $locale->id,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $written = 0;

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            if ($overwrite) {
                TranslationValue::upsert($chunk, ['key_id', 'locale_id'], ['value', 'updated_at']);
                $written += count($chunk);
            } else {
                $written += TranslationValue::insertOrIgnore($chunk);
            }
        }

        return $written;
    }

    /**
     * Create any missing keys for the group and return [key => id].
     *
     * @param  array<int, string|int>  $keys
     */
    protected function ensureKeys(string $namespace, string $group, array $keys, $now): array
    {
        $keys = array_map('strval', $keys);

        foreach (array_chunk($keys, self::CHUNK_SIZE) as $chunk) {
            TranslationKey::insertOrIgnore(array_map(fn ($key) => [
                'namespace' => $namespace,
                'group' => $group,
                'key' => $key,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }

        $ids = [];
        foreach (array_chunk($keys, self::CHUNK_SIZE) as $chunk) {
            $ids += TranslationKey::where('namespace', $namespace)
                ->where('group', $group)
                ->whereIn('key', $chunk)
                ->pluck('id', 'key')
                ->all();
        }

        return $ids;
    }
}
