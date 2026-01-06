<?php

namespace PhpJunior\Glosa\Actions;

use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationValue;

class DeleteLocaleAction
{
    /**
     * @param int $id
     * @return void
     */
    public function execute(int $id): void
    {
        $locale = Locale::findOrFail($id);
        TranslationValue::where('locale_id', $locale->id)->delete();
        $locale->delete();
    }
}
