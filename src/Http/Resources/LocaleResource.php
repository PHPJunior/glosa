<?php

namespace PhpJunior\Glosa\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LocaleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'is_default' => (bool) $this->is_default,
        ];
    }
}
