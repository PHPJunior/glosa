<?php

namespace PhpJunior\Glosa\Actions;

use PhpJunior\Glosa\Models\TranslationKey;

class CreateKeyAction
{
    public function execute(array $data): TranslationKey
    {
        return TranslationKey::firstOrCreate([
            'key' => $data['key'],
            'group' => $data['group'],
        ]);
    }
}
