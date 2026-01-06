<?php

namespace PhpJunior\Glosa\Actions;

use PhpJunior\Glosa\Models\Locale;

class UpdateLocaleAction
{
    /**
     * @param int $id
     * @param array $data
     * @return Locale
     */
    public function execute(int $id, array $data): Locale
    {
        $locale = Locale::findOrFail($id);

        if (($data['is_default'] ?? false) && !$locale->is_default) {
            Locale::resetDefault($id);
        }

        $locale->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'is_default' => $data['is_default'] ?? $locale->is_default,
        ]);

        return $locale;
    }
}
