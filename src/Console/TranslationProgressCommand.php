<?php

namespace PhpJunior\Glosa\Console;

use Illuminate\Console\Command;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Queries\GetTranslationProgressQuery;

class TranslationProgressCommand extends Command
{
    protected $signature = 'glosa:progress
                            {--locale=* : Only show the given locale(s)}
                            {--namespace= : Only count keys in the given namespace ("*" for global keys)}
                            {--group= : Only count keys in the given group, e.g. "auth" or "client::labels"}
                            {--by-group : Show the progress of each group}
                            {--missing : List the keys that are missing a translation}
                            {--min= : Fail when any locale is below this percentage}';

    protected $description = 'Show how much of each locale has been translated';

    public function handle(GetTranslationProgressQuery $query): int
    {
        $locales = Locale::query()
            ->when($this->option('locale'), fn ($q, $codes) => $q->whereIn('code', $codes))
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get();

        $unknown = array_diff($this->option('locale'), $locales->pluck('code')->all());

        if ($unknown !== []) {
            $this->components->error('Unknown locale(s): '.implode(', ', $unknown).'.');

            return self::FAILURE;
        }

        if ($locales->isEmpty()) {
            $this->components->warn('No locales found.');

            return self::SUCCESS;
        }

        $filters = [
            'namespace' => $this->option('namespace'),
            'group' => $this->option('group'),
        ];

        $summary = $query->summary($locales, $filters);

        $this->table(
            ['Locale', 'Translated', 'Missing', 'Progress'],
            $summary->map(fn ($stats, $code) => [
                $code.($locales->firstWhere('code', $code)->is_default ? ' (default)' : ''),
                "{$stats['translated']} / {$stats['total']}",
                $stats['missing'],
                $this->bar($stats['percent']).' '.$this->percent($stats['percent']),
            ])->values()
        );

        if ($this->option('by-group')) {
            $this->table(
                ['Group', ...$locales->pluck('code')],
                $query->byGroup($locales, $filters)->map(fn ($stats, $group) => [
                    $group === '*' ? '* (json)' : $group,
                    ...$stats->map(fn ($row) => $this->percent($row['percent'])." ({$row['missing']} missing)")->values(),
                ])->values()
            );
        }

        if ($this->option('missing')) {
            foreach ($locales as $locale) {
                $missing = $query->missingKeys($locale, $filters);

                if ($missing->isNotEmpty()) {
                    $this->newLine();
                    $this->components->twoColumnDetail("<fg=yellow>Missing in [{$locale->code}]</>", "<fg=yellow>{$missing->count()}</>");
                    $missing->each(fn ($key) => $this->line("  {$key}"));
                }
            }
        }

        return $this->checkThreshold($summary);
    }

    protected function checkThreshold($summary): int
    {
        if ($this->option('min') === null) {
            return self::SUCCESS;
        }

        $min = (float) $this->option('min');
        $below = $summary->filter(fn ($stats) => $stats['percent'] < $min);

        if ($below->isNotEmpty()) {
            $this->newLine();
            $this->components->error(
                'Below '.$this->percent($min).': '.$below->map(fn ($stats, $code) => "{$code} (".$this->percent($stats['percent']).')')->implode(', ').'.'
            );

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function bar(float $percent, int $width = 20): string
    {
        $filled = (int) round($percent / 100 * $width);

        return str_repeat('█', $filled).str_repeat('░', $width - $filled);
    }

    protected function percent(float $percent): string
    {
        return number_format($percent, 1).'%';
    }
}
