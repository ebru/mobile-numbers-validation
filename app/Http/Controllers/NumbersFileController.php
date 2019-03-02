<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Imports\NumbersFileImport;
use Maatwebsite\Excel\Facades\Excel;

class NumbersFileController extends Controller
{
    public function process(Request $request)
    {
        if ($request->hasFile('numbers_file')) {
        
            $array = Excel::toArray(new NumbersFileImport, request()->file('numbers_file'));

            var_dump($array); exit;

            return response()->json('OK')
                ->setStatusCode(Response::HTTP_OK);
        }
    }
}
