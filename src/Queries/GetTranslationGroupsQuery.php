<?php

namespace PhpJunior\Glosa\Queries;

use PhpJunior\Glosa\Models\TranslationKey;

class GetTranslationGroupsQuery
{
    public function get()
    {
        return TranslationKey::select('namespace', 'group')
            ->distinct()
            ->get()
            ->map->qualifiedGroup()
            ->values();
    }
}
