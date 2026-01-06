<?php

namespace PhpJunior\Glosa\Actions;

use PhpJunior\Glosa\Models\TranslationKey;

class UpdateKeyAction
{
    /**
     * @param int $id
     * @param array $data
     * @return TranslationKey
     */
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
