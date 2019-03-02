<?php

namespace App\Exports;

use App\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class NumbersFileExport implements FromCollection, WithHeadings	
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Number::all();
    }

    public function headings(): array
    {
        return [
            'number_id',
            'number_value',
            'is_valid',
            'is_modified',
            'created_at',
            'updated_at'
        ];
    }
}
