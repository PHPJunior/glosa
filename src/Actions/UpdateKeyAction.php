<?php

namespace PhpJunior\Glosa\Actions;

use PhpJunior\Glosa\Models\TranslationKey;

class UpdateKeyAction
{
    public function execute(int $id, array $data): TranslationKey
    {
        $key = TranslationKey::findOrFail($id);

        $key->update([
            'key' => $data['key'],
            'group' => $data['group'],
        ]);

        return $key;
    }
}
