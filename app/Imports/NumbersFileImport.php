<?php

namespace App\Imports;

use App\Number;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class NumbersFileImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Number([
            'number_id' => $row['id'],
            'number_value' => $row['sms_phone']
         ]);
    }
}
