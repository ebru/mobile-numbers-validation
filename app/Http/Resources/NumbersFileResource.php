<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NumbersFileResource extends JsonResource
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
            'file' => [
                'hash_name' => $this->file_hash_name,
                'original_path' => $this->original_file_path,
                'modified_path' => $this->modified_file_path,
                'details' => [
                    'count' => [
                        'total_numbers' => $this->total_numbers_count,
                        'valid_numbers' => $this->valid_numbers_count,
                        'corrected_numbers' => $this->corrected_numbers_count,
                        'not_valid_numbers' => $this->not_valid_numbers_count
                    ]
                ]
            ]
        ];
    }
}
