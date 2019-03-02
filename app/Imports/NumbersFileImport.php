<?php

namespace App\Imports;

use App\Number;
use Maatwebsite\Excel\Concerns\ToModel;

class NumbersFileImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Number([
            'number_id'     => $row[0],
            'number_value'    => $row[1]
         ]);
    }
}
