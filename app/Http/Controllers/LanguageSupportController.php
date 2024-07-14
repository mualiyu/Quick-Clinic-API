<?php

namespace App\Http\Controllers;

use App\Models\LanguageSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LanguageSupportController extends Controller
{
    public function index()
    {
        $languages = LanguageSupport::all();

        if (!count($languages)>0) {

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

    public function addLanguage(Request $request)
    {
        if ($request->user()->tokenCan('admin')) {

            $user = $request->user(); // Assuming user is authenticated

            // Validate incoming request data
            $validator = Validator::make($request->all(), [
                'language_name' => 'required|string',
                'language_code' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            LanguageSupport::create($request->all());

            return response()->json([
                'status' => true,
                'data' => LanguageSupport::all(),
                'message' => "Success"
            ], 200);

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }

    }

    public function deleteLanguage(Request $request, $languageSupport)
    {
        if ($request->user()->tokenCan('admin')) {

            if (count(LanguageSupport::where('id', $languageSupport)->get())>0) {

                LanguageSupport::where('id', $languageSupport)->delete();
                // $languageSupport->delete();

                return response()->json([
                    'status' => true,
                    'message' => 'Language deleted successfully'
                ], 200);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => 'Language not found'
                ], 404);
            }

            return response()->json([
                'status' => false,
                'message' => 'Language not found'
            ], 404);
        }else {
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }
}
