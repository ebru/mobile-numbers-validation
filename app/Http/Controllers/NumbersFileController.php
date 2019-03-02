<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Imports\NumbersFileImport;
use App\Exports\NumbersFileExport;
use App\Number;
use Maatwebsite\Excel\Facades\Excel;
use Storage;

class NumbersFileController extends Controller
{
    public function process(Request $request)
    {
        if ($request->hasFile('numbers_file')) {
            $extension = $request->file('numbers_file')->getClientOriginalExtension();
            $fileId = uniqid();
            $fileName = $fileId.'.'.$extension;

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
                } elseif ($this->correctNumber((string) $row['sms_phone'])['is_corrected']) {
                    $modifiedDetails = $this->correctNumber((string) $row['sms_phone']);

                    $number->number_value = $modifiedDetails['modified_number'];
                    $number->is_valid = true;
                    $number->is_modified = true;
                    $number->before_modified_value = $row['sms_phone'];
                } else {
                    $number->is_valid = false;
                    $number->is_modified = false;
                }

                $number->save();
            }
            
            Excel::store(new NumbersFileExport(), "files/modified/{$fileName}", 'public');

            $modifiedPath = Storage::url("files/modified/{$fileName}");

            $response = [
                'file' => [
                    'id' => $fileId,
                    'original_path' => $originalPath,
                    'modified_path' => $modifiedPath,
                    'details' => [
                        'count' => [
                            'total_numbers' => 'test',
                            'valid_numbers' => 'test',
                            'not_valid_numbers' => 'test'
                        ]
                    ]
                ]
            ];

            return response()->json($response)
                ->setStatusCode(Response::HTTP_OK);
        }
    }

    public function correctNumber(string $number): array
    {
        $isCorrected = false;
        $modifiedNumber = null;

        if (strlen($number) === 9) {
            $addedCountryCodeNumber = '27'.$number;
            if ($this->validateNumber($addedCountryCodeNumber)) {
                $isCorrected = true;
                $modifiedNumber = $addedCountryCodeNumber;
            }
        }

        if ($this->validateNumber(explode("_", $number)[0])) {
            $updatedNumber = explode("_", $number)[0];
            
            $isCorrected = true;
            $modifiedNumber = $updatedNumber;
        }

        return [
            'is_corrected' => $isCorrected,
            'modified_number' => $modifiedNumber
        ];
    }

    public function validateNumber(string $number): bool
    {
        if (preg_match('/^(\+?27|0)[6-8][0-9]{8}$/', $number) === 1) {
            return true;
        }

        return false;
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
}
