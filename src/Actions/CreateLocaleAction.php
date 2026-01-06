<?php

namespace PhpJunior\Glosa\Actions;

use PhpJunior\Glosa\Models\Locale;

class CreateLocaleAction
{
    /**
     * @param array $data
     * @return Locale
     */
    public function execute(array $data): Locale
    {
        if ($data['is_default'] ?? false) {
            Locale::resetDefault();
        }

        return Locale::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'is_default' => $data['is_default'] ?? false,
        ]);
    }
}
