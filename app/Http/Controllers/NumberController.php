<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        $numberStr = $request->input('mobile_number');

        if ($this->validateNumber($numberStr)) {
            //
        } elseif ($this->correctNumber($numberStr)['is_corrected']) {
            //
        } else {
            //
        }

        $response = [];

        return response()->json($response)
            ->setStatusCode(Response::HTTP_OK);
    }
}
