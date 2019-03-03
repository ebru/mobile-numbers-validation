<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Number;
use App\Http\Resources\NumberResource;
use Validator;

class NumberController extends BaseController
{
    /**
     * Processing of the requested number
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

        $number = new Number();
        $number->number_value = $request->input('mobile_number');

        if ($this->validateNumber($request->input('mobile_number'))) {
            $number->is_valid = true;
            $number->is_modified = false;
        } elseif ($this->correctNumber($request->input('mobile_number'))['is_corrected']) {
            $modifiedDetails = $this->correctNumber($request->input('mobile_number'));

            $number->number_value = $modifiedDetails['modified_number'];
            $number->is_valid = true;
            $number->is_modified = true;
            $number->before_modified_value = $request->input('mobile_number');
        } else {
            $number->is_valid = false;
            $number->is_modified = false;
        }

        return new NumberResource($number);
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
            'mobile_number' => 'required'
        ];
    
        $validator = Validator::make($request->all(), $rules);

        return $validator;
    }
}
