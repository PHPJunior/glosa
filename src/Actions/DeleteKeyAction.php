<?php

namespace PhpJunior\Glosa\Actions;

use PhpJunior\Glosa\Models\TranslationKey;
use PhpJunior\Glosa\Models\TranslationValue;

class DeleteKeyAction
{
    /**
     * @param int $id
     * @return void
     */
    public function execute(int $id): void
    {
        $key = TranslationKey::findOrFail($id);
        TranslationValue::where('key_id', $key->id)->delete();
        $key->delete();
    }
}
