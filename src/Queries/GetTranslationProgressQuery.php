<?php

namespace PhpJunior\Glosa\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;

class GetTranslationProgressQuery
{
    /**
     * Translated / total key counts per locale.
     *
     * A key counts as translated for a locale when its value is neither null nor empty.
     *
     * @param  Collection<int, Locale>  $locales
     * @param  array{namespace?: string|null, group?: string|null}  $filters
     * @return Collection<string, array{total: int, translated: int, missing: int, percent: float}>
     */
    public function summary(Collection $locales, array $filters = []): Collection
    {
        $total = $this->filterKeys(TranslationKey::query(), $filters)->count();

        $translated = $this->translatedValues($filters)
            ->select('glosa_values.locale_id')
            ->selectRaw('count(*) as aggregate')
            ->groupBy('glosa_values.locale_id')
            ->pluck('aggregate', 'locale_id');

        return $locales->mapWithKeys(fn (Locale $locale) => [
            $locale->code => $this->stats($total, (int) ($translated[$locale->id] ?? 0)),
        ]);
    }

    /**
     * Translated / total key counts per group and locale, keyed by the qualified group (e.g. 'client::labels').
     *
     * @param  Collection<int, Locale>  $locales
     * @param  array{namespace?: string|null, group?: string|null}  $filters
     * @return Collection<string, Collection<string, array{total: int, translated: int, missing: int, percent: float}>>
     */
    public function byGroup(Collection $locales, array $filters = []): Collection
    {
        $totals = $this->filterKeys(TranslationKey::query(), $filters)
            ->select(['namespace', 'group'])
            ->selectRaw('count(*) as aggregate')
            ->groupBy(['namespace', 'group'])
            ->orderBy('namespace')
            ->orderBy('group')
            ->get();

        $translated = $this->translatedValues($filters)
            ->select(['glosa_values.locale_id', 'glosa_keys.namespace', 'glosa_keys.group'])
            ->selectRaw('count(*) as aggregate')
            ->groupBy(['glosa_values.locale_id', 'glosa_keys.namespace', 'glosa_keys.group'])
            ->get()
            ->groupBy(fn ($row) => $row->namespace."\0".$row->group)
            ->map(fn ($rows) => $rows->pluck('aggregate', 'locale_id'));

        return $totals->mapWithKeys(function (TranslationKey $group) use ($locales, $translated) {
            $counts = $translated->get($group->namespace."\0".$group->group, collect());

            return [
                $group->qualifiedGroup() => $locales->mapWithKeys(fn (Locale $locale) => [
                    $locale->code => $this->stats((int) $group->aggregate, (int) ($counts[$locale->id] ?? 0)),
                ]),
            ];
        });
    }

    /**
     * Full keys (e.g. 'client::labels.app_url') that have no value for the locale.
     *
     * @param  array{namespace?: string|null, group?: string|null}  $filters
     * @return Collection<int, string>
     */
    public function missingKeys(Locale $locale, array $filters = []): Collection
    {
        return $this->filterKeys(TranslationKey::query(), $filters)
            ->whereDoesntHave('values', function ($values) use ($locale) {
                $values->where('locale_id', $locale->id)
                    ->whereNotNull('value')
                    ->where('value', '!=', '');
            })
            ->orderBy('namespace')
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->map->fullKey()
            ->values();
    }

    protected function translatedValues(array $filters)
    {
        return TranslationValue::query()
            ->join('glosa_keys', 'glosa_keys.id', '=', 'glosa_values.key_id')
            ->whereNotNull('glosa_values.value')
            ->where('glosa_values.value', '!=', '')
            ->when($filters['namespace'] ?? null, fn ($q, $namespace) => $q->where('glosa_keys.namespace', $namespace))
            ->when($filters['group'] ?? null, function ($q, $group) {
                [$namespace, $group] = TranslationKey::parseGroup($group);

                $q->where('glosa_keys.namespace', $namespace)->where('glosa_keys.group', $group);
            });
    }

    protected function filterKeys(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['namespace'] ?? null, fn ($q, $namespace) => $q->where('namespace', $namespace))
            ->when($filters['group'] ?? null, function ($q, $group) {
                [$namespace, $group] = TranslationKey::parseGroup($group);

                $q->where('namespace', $namespace)->where('group', $group);
            });
    }

    /**
     * @return array{total: int, translated: int, missing: int, percent: float}
     */
    protected function stats(int $total, int $translated): array
    {
        return [
            'total' => $total,
            'translated' => $translated,
            'missing' => $total - $translated,
            'percent' => $total === 0 ? 100.0 : round($translated / $total * 100, 1),
        ];
    }
}
