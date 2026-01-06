<?php

namespace PhpJunior\Glosa\Queries;


use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Http\Resources\TranslationKeyResource;
use PhpJunior\Glosa\Http\Resources\LocaleResource;

class GetGroupedTranslationsQuery
{
    public function get(array $filters = [])
    {
        $limit = $filters['limit'] ?? 20;

        $keys = TranslationKey::with(['values.locale'])
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where(function ($sub) use ($search) {
                    $sub->where('key', 'like', "%{$search}%")
                        ->orWhere('group', 'like', "%{$search}%");
                });
            })
            ->when(!empty($filters['group']), function ($q) use ($filters) {
                $q->where('group', $filters['group']);
            })
            ->when(!empty($filters['missing_locale']), function ($q) use ($filters) {
                $localeCode = $filters['missing_locale'];
                $q->whereDoesntHave('values', function ($values) use ($localeCode) {
                    $values->whereHas('locale', function ($locale) use ($localeCode) {
                        $locale->where('code', $localeCode);
                    })->whereNotNull('value')->where('value', '!=', '');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        $locales = Locale::all();

        return TranslationKeyResource::collection($keys)->additional([
            'locales' => LocaleResource::collection($locales)
        ]);
    }
}
