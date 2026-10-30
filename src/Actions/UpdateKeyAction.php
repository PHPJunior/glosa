<?php

namespace PhpJunior\Glosa\Actions;

use PhpJunior\Glosa\Models\TranslationKey;

class UpdateKeyAction
{
    public function execute(int $id, array $data): TranslationKey
    {
        $key = TranslationKey::findOrFail($id);

        [$namespace, $group] = TranslationKey::parseGroup($data['group']);

        $key->update([
            'namespace' => $namespace,
            'key' => $data['key'],
            'group' => $group,
        ]);

        return $key;
    }
}
