<?php

namespace PhpJunior\Glosa\Queries;

use PhpJunior\Glosa\Models\TranslationKey;

class GetTranslationGroupsQuery
{
    public function get()
    {
        return TranslationKey::select('group')->distinct()->pluck('group');
    }
}
