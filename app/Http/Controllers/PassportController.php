<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use Validator;

class PassportController extends Controller
{
    /**
     * Creates an api token while registering a new user
     *
     * @param Request $request
     * @return Response
     */
    public function register(Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ];
    
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return [
                'error' => $validator->errors()->first()
            ];
        }
 
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
 
        $token = $user->createToken('mobile-numbers-validation')->accessToken;
 
        return response()->json(['token' => $token])
            ->setStatusCode(Response::HTTP_OK);
    }
}
