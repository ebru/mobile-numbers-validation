<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Imports\NumbersFileImport;
use App\Exports\NumbersFileExport;
use App\Number;
use App\NumbersFile;
use App\Http\Resources\NumbersFileResource;
use Maatwebsite\Excel\Facades\Excel;
use Storage;
use Validator;

class NumbersFileController extends Controller
{
    public function process(Request $request)
    {
        $validator = $this->validateRequest($request);
        
        if ($validator->fails()) {
            return [
                'error' => $validator->errors()->first()
            ];
        }

        $uploadedFile = $request->file('numbers_file');
        $extension = $uploadedFile->getClientOriginalExtension();
        $fileHashName = explode('.', $uploadedFile->hashName())[0];
        $fileName = $fileHashName.'.'.$extension;
            
        $data = Excel::toArray(new NumbersFileImport, $uploadedFile);

        $validNumbersCount = 0;
        $correctedNumbersCount = 0;
        $notValidNumbersCount = 0;

        foreach ($data[0] as $row) {
            $number = new Number();

            $number->number_id = $row['id'];
            $number->number_value = $row['sms_phone'];
                    
            if ($this->validateNumber((string) $row['sms_phone'])) {
                $number->is_valid = true;
                $number->is_modified = false;

                $validNumbersCount++;
            } elseif ($this->correctNumber((string) $row['sms_phone'])['is_corrected']) {
                $modifiedDetails = $this->correctNumber((string) $row['sms_phone']);

                $number->number_value = $modifiedDetails['modified_number'];
                $number->is_valid = true;
                $number->is_modified = true;
                $number->before_modified_value = $row['sms_phone'];

                $validNumbersCount++;
                $correctedNumbersCount++;
            } else {
                $number->is_valid = false;
                $number->is_modified = false;

                $notValidNumbersCount++;
            }

            $number->save();
        }

        $originalPath = $this->saveUploadedFile($uploadedFile, $fileName, 'original');
        $modifiedPath = $this->storeExportFile($fileName, 'modified');

        $numbersFile = new NumbersFile();

        $numbersFile->file_hash_name = $fileHashName;
        $numbersFile->original_file_path = $originalPath;
        $numbersFile->modified_file_path = $modifiedPath;
        $numbersFile->total_numbers_count = count($data[0]);
        $numbersFile->valid_numbers_count = $validNumbersCount;
        $numbersFile->corrected_numbers_count = $correctedNumbersCount;
        $numbersFile->not_valid_numbers_count = $notValidNumbersCount;

        if ($numbersFile->save()) {
            $file = DB::table('numbers_files')->where('file_hash_name', $fileHashName)->first();
            $numbersFile->file_id = $file->file_id;

            return new NumbersFileResource($numbersFile);
        }
    }

    /**
     * Attempts to correct number format to validate
     *
     * @param string $number
     * @return array
     */
    public function correctNumber(string $number): array
    {
        // Check if country code is missing
        if (strlen($number) === 9) {
            $addedCountryCodeNumber = '27'.$number;

            if ($this->validateNumber($addedCountryCodeNumber)) {
                return [
                    'is_corrected' => true,
                    'modified_number' => $addedCountryCodeNumber
                ];
            }
        }

        // Check if updated number is valid after eliminating the deleted part
        $parsedUpdatedNumber = explode("_", $number)[0];

        if ($this->validateNumber($parsedUpdatedNumber)) {
            return [
                'is_corrected' => true,
                'modified_number' => $parsedUpdatedNumber
            ];
        }

        return [
            'is_corrected' => false,
            'modified_number' => null
        ];
    }

    /**
     * Validates the number if it is formatted correctly for South America
     *
     * @param string $number
     * @return boolean
     */
    public function validateNumber(string $number): bool
    {
        if (preg_match('/^(\+?27|0)[6-8][0-9]{8}$/', $number) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Save file to public storage
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $fileName
     * @param string $directory
     * @return string
     */
    public function saveUploadedFile(\Illuminate\Http\UploadedFile $file, string $fileName, string $directory): string
    {
        Storage::disk('public')->putFileAs("files/{$directory}", $file, $fileName);

        return Storage::url("files/{$directory}/{$fileName}");
    }

    /**
     * Stores the export file to public storage
     *
     * @param string $fileName
     * @param string $directory
     * @return string
     */
    public function storeExportFile(string $fileName, string $directory): string
    {
        Excel::store(new NumbersFileExport(), "files/{$directory}/{$fileName}", 'public');

        return Storage::url("files/{$directory}/{$fileName}");
    }

    public function validateRequest(Request $request)
    {
        $rules = [
            'numbers_file' => 'required|mimes:csv,txt'
        ];
    
        $validator = Validator::make($request->all(), $rules);

        return $validator;
    }
}
