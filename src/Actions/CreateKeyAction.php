<?php

namespace PhpJunior\Glosa\Actions;

use PhpJunior\Glosa\Models\TranslationKey;

class CreateKeyAction
{
    public function execute(array $data): TranslationKey
    {
        [$namespace, $group] = TranslationKey::parseGroup($data['group']);

        return TranslationKey::firstOrCreate([
            'namespace' => $namespace,
            'key' => $data['key'],
            'group' => $group,
        ]);
    }
}
