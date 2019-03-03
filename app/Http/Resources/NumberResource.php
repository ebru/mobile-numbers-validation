<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NumberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'number' => [
                'value' => $this->number_value,
                'is_valid' => $this->is_valid,
                'is_modified' => $this->is_modified,
                'before_modified_value' => $this->before_modified_value
            ]
        ];
    }
}
