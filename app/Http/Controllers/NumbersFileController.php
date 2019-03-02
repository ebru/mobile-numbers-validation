<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Imports\NumbersFileImport;
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
}
