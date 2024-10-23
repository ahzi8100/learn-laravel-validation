<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FormController extends Controller
{
    public function login(Request $request): Response
    {
        try {
            $data = $request->validate([
                'username' => 'required',
                'password' => 'required'
            ]);
            return response('OK', Response::HTTP_OK);
        }catch (ValidationException $validationException){
            return response($validationException->errors(), Response::HTTP_BAD_REQUEST);
        }
    }
}
