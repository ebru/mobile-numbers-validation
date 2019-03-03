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

class NumbersFileController extends BaseController
{
    /**
     * Processing of the requested file
     *
     * @param Request $request
     * @return Illuminate\\Http\\JsonResponse
     */
    public function process(Request $request)
    {
        $validator = $this->validateRequest($request);
        
        if ($validator->fails()) {
            return [
                'error' => $validator->errors()->first()
            ];
        }

        $parsedFileDetails = $this->parseFile($request->file('numbers_file'));

        foreach ($parsedFileDetails['numbers'] as $number) {
            if ($this->getNumberById($number->number_id)) {
                return response()->json(['error' => 'The file contains duplicated number ids.'])
                    ->setStatusCode(Response::HTTP_BAD_REQUEST);
            }

            try {
                $number->save();
            } catch (\Exception $e) {
                return response()->json(['error' => 'The number could not be saved in database.'])
                    ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $storedFileDetails = $this->storeFiles($request->file('numbers_file'));

        $numbersFile = new NumbersFile();

        $numbersFile->file_hash_name = $storedFileDetails['file_hash_name'];
        $numbersFile->original_file_path = $storedFileDetails['original_file_path'];
        $numbersFile->modified_file_path = $storedFileDetails['modified_file_path'];
        $numbersFile->total_numbers_count = $parsedFileDetails['counts']['total_numbers'];
        $numbersFile->valid_numbers_count = $parsedFileDetails['counts']['valid_numbers'];
        $numbersFile->corrected_numbers_count = $parsedFileDetails['counts']['corrected_numbers'];
        $numbersFile->not_valid_numbers_count = $parsedFileDetails['counts']['not_valid_numbers'];

        try {
            $numbersFile->save();
            $numbersFile->file_id = $this->getFileByHashName($storedFileDetails['file_hash_name'])->file_id;

            return new NumbersFileResource($numbersFile);
        } catch (\Exception $e) {
            return response()->json(['error' => 'The file process could not be saved in database.'])
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get file details by file id
     *
     * @param Request $request
     * @param integer $id
     * @return Illuminate\\Http\\JsonResponse
     */
    public function get(Request $request, int $id)
    {
        $file = DB::table('numbers_files')->where('file_id', $id)->first();

        if (!empty($file)) {
            $numbersFile = new NumbersFile();

            $numbersFile->file_id = $file->file_id;
            $numbersFile->file_hash_name = $file->file_hash_name;
            $numbersFile->original_file_path = $file->original_file_path;
            $numbersFile->modified_file_path = $file->modified_file_path;
            $numbersFile->total_numbers_count = $file->total_numbers_count;
            $numbersFile->valid_numbers_count = $file->valid_numbers_count;
            $numbersFile->corrected_numbers_count =  $file->corrected_numbers_count;
            $numbersFile->not_valid_numbers_count = $file->not_valid_numbers_count;

            return new NumbersFileResource($numbersFile);
        }

        return response()->json(['error' => 'There is no file with this id.'])
            ->setStatusCode(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Store the original and modified versions of the file requested
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $fileName
     * @return array
     */
    public function storeFiles(\Illuminate\Http\UploadedFile $file): array
    {
        $extension = $file->getClientOriginalExtension();
        $fileHashName = explode('.', $file->hashName())[0];
        $fileName = $fileHashName.'.'.$extension;
        
        return [
            'file_hash_name' => $fileHashName,
            'original_file_path' => $this->saveUploadedFile($file, $fileName, 'original'),
            'modified_file_path' => $this->storeExportFile($fileName, 'modified')
        ];
    }

    /**
     * Parse numbers data from uploaded file
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array
     */
    public function parseFile(\Illuminate\Http\UploadedFile $file): array
    {
        $data = Excel::toArray(new NumbersFileImport, $file)[0];

        $validNumbersCount = 0;
        $correctedNumbersCount = 0;
        $notValidNumbersCount = 0;

        $numbers = [];

        foreach ($data as $row) {
            $numberStr = (string) $row['sms_phone'];

            $number = new Number();
            $number->number_id = $row['id'];
            $number->number_value = $row['sms_phone'];
                    
            if ($this->validateNumber($numberStr)) {
                $number->is_valid = true;
                $number->is_modified = false;

                $validNumbersCount++;
            } elseif ($this->correctNumber($numberStr)['is_corrected']) {
                $modifiedDetails = $this->correctNumber($numberStr);

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

            array_push($numbers, $number);
        }

        return [
            'numbers' => $numbers,
            'counts' => [
                'total_numbers' => count($data),
                'valid_numbers' => $validNumbersCount,
                'corrected_numbers' => $correctedNumbersCount,
                'not_valid_numbers' => $notValidNumbersCount
            ]
        ];
    }

    /**
     * Get number from database with number id
     *
     * @param float $numberId
     * @return object|null
     */
    public function getNumberById(float $numberId)
    {
        $file = DB::table('numbers')->where('number_id', $numberId)->first();

        return $file;
    }

    /**
    * Get file from database with hash name
    *
    * @param string $fileHashName
    * @return object|null
    */
    public function getFileByHashName(string $fileHashName)
    {
        $file = DB::table('numbers_files')->where('file_hash_name', $fileHashName)->first();

        return $file;
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

    /**
     * Validate the request
     *
     * @param Request $request
     * @return Validator
     */
    public function validateRequest(Request $request)
    {
        $rules = [
            'numbers_file' => 'required|mimes:csv,txt'
        ];
    
        $validator = Validator::make($request->all(), $rules);

        return $validator;
    }
}
