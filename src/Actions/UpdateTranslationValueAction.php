<?php

namespace PhpJunior\Glosa\Actions;

use PhpJunior\Glosa\Models\Locale;
use PhpJunior\Glosa\Models\TranslationValue;

class UpdateTranslationValueAction
{
    /**
     * @param array $data
     * @return void
     */
    public function execute(array $data): void
    {
        $locale = Locale::where('code', $data['locale'])->firstOrFail();

        TranslationValue::updateOrCreate(
            [
                'locale_id' => $locale->id,
                'key_id' => $data['key_id']
            ],
            ['value' => $data['value']]
        );
    }
}
