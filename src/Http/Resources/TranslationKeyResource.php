<?php

namespace PhpJunior\Glosa\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TranslationKeyResource extends JsonResource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $values = [];
        foreach ($this->values as $value) {
            if ($value->locale) {
                $values[$value->locale->code] = $value->value;
            }
        }

        // Construct full dotted key
        $fullKey = $this->group === '*' ? $this->key : "{$this->group}.{$this->key}";

        return [
            'id' => $this->id,
            'full_key' => $fullKey,
            'group' => $this->group,
            'key_name' => $this->key, // 'key' is reserved in some frontend contexts or confusing, keeping 'key_name'
            'values' => $values,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
