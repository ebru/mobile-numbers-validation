<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Imports\NumbersFileImport;
use App\Number;
use Maatwebsite\Excel\Facades\Excel;
use Storage;

class NumbersFileController extends Controller
{
    public function process(Request $request)
    {
        if ($request->hasFile('numbers_file')) {
        
            // Database import
            // Excel::import(new NumbersFileImport, request()->file('numbers_file'));

            $extension = $request->file('numbers_file')->getClientOriginalExtension();
            $fileName = uniqid().'.'.$extension;

            Storage::disk('public')->putFileAs('files/original', $request->file('numbers_file'), $fileName);

            $originalPath = Storage::url("files/original/{$fileName}");
            
            $array = Excel::toArray(new NumbersFileImport, request()->file('numbers_file'));

            foreach ($array[0] as $row) {
                $number = new Number();

                $number->number_id = $row['id'];
                $number->number_value = $row['sms_phone'];
                    
                if ($this->validateNumber((string) $row['sms_phone'])) {
                    $number->is_valid = true;
                    $number->is_modified = false;
                } else {
                    $number->is_valid = false;
                    $number->is_modified = false;
                }

                $number->save();
            }

            $response = [
                'file' => [
                    'original_path' => $originalPath,
                    'data' => $array
                ]
            ];

            return response()->json($response)
                ->setStatusCode(Response::HTTP_OK);
        }
    }

    public function putFile($path, $file, $options = [])
    {
        return $this->putFileAs($path, $file, $file->hashName(), $options);
    }

    public function hashName($path = null)
    {
        if ($path) {
            $path = rtrim($path, '/').'/';
        }

        $hash = $this->hashName ?: $this->hashName = Str::random(40);

        return $path.$hash.'.'.$this->guessExtension();
    }

    public function validateNumber(string $number): bool
    {
        if (preg_match('/^(\+?27|0)[6-8][0-9]{8}$/', $number) === 1) {
            return true;
        }

        return false;
    }
}
