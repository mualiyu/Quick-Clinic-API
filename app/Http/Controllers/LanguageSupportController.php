<?php

namespace App\Http\Controllers;

use App\Models\LanguageSupport;
use Illuminate\Http\Request;

class LanguageSupportController extends Controller
{
    public function index()
    {
        $languages = LanguageSupport::all();

        if (count($languages)>0) {

            return response()->json([
                'status' => false,
                'message' => trans('No language found on the system!')
            ], 200);
        }

        return response()->json([
            'status' => true,
            'data' => $languages,
            'message' => "Successful"
        ], 200);
    }
}
