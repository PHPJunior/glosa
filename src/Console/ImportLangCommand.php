<?php

namespace PhpJunior\Glosa\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use PhpJunior\Glosa\Actions\ImportLangDirectoryAction;

class ImportLangCommand extends Command
{
    protected $signature = 'glosa:import
                            {path?* : Lang directories or glob patterns to import, e.g. "app/Modules/*/lang" (defaults to the application lang path)}
                            {--locale=* : Only import the given locale(s)}
                            {--namespace= : Translation namespace for PHP files (detected from loadTranslationsFrom() registrations by default)}
                            {--overwrite : Overwrite translations that already exist in the database}';

    protected $description = 'Import translations from Laravel lang directories into Glosa';

    public function handle(ImportLangDirectoryAction $action): int
    {
        $stats = [];
        $registered = $this->registeredNamespaces();

        try {
            foreach ($this->resolvePaths((array) $this->argument('path')) as $path) {
                $namespace = $this->option('namespace') ?: ($registered[realpath($path) ?: $path] ?? '*');
                $label = $namespace === '*' ? 'global' : $namespace;

                $this->components->info("Importing translations from [{$path}] (namespace: {$label}).");

                $result = $action->execute($path, $this->option('locale'), (bool) $this->option('overwrite'), $namespace);

                foreach ($result as $locale => $row) {
                    $stats[$locale]['found'] = ($stats[$locale]['found'] ?? 0) + $row['found'];
                    $stats[$locale]['written'] = ($stats[$locale]['written'] ?? 0) + $row['written'];
                }
            }
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($stats === []) {
            $this->components->warn('No translations found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Locale', 'Found', $this->option('overwrite') ? 'Written' : 'New'],
            collect($stats)->map(fn ($row, $locale) => [$locale, $row['found'], $row['written']])->values()
        );

        return self::SUCCESS;
    }

    /**
     * Namespaces registered through loadTranslationsFrom(), keyed by real path.
     *
     * @return array<string, string>
     */
    protected function registeredNamespaces(): array
    {
        $namespaces = [];

        foreach ($this->laravel['translator']->getLoader()->namespaces() as $namespace => $hint) {
            $namespaces[realpath($hint) ?: rtrim($hint, '/\\')] = $namespace;
        }

        return $namespaces;
    }

    /**
     * Resolve the given paths to absolute directories, expanding glob patterns.
     *
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    protected function resolvePaths(array $paths): array
    {
        if ($paths === []) {
            return [lang_path()];
        }

        $resolved = [];

        foreach ($paths as $path) {
            $path = $this->absolutePath($path);

            if (strpbrk($path, '*?[{') === false) {
                $resolved[] = $path;

                continue;
            }

            $matches = glob($path, GLOB_ONLYDIR | (defined('GLOB_BRACE') ? GLOB_BRACE : 0)) ?: [];

            if ($matches === []) {
                throw new InvalidArgumentException("No directories match [{$path}].");
            }

            array_push($resolved, ...$matches);
        }

        return array_values(array_unique($resolved));
    }

    protected function absolutePath(string $path): string
    {
        $path = rtrim($path, '/\\');

        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }

        return base_path($path);
    }
}
