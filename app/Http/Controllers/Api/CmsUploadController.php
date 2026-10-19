<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CmsUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CmsUploadController extends Controller
{
    public function __construct(private readonly CmsUploadService $uploads)
    {
    }

    public function document(Request $request): JsonResponse
    {
        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            return response()->json(['status' => 'error', 'message' => 'No file was uploaded or there was an error uploading the file.'], 422);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, only PDF, DOC, and DOCX files are allowed.'], 422);
        }

        $name = $this->uploads->safeName($file, 'document');
        try {
            $this->uploads->store($file, $this->uploads->formsDirectory(), $name, true);
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() === 'DUPLICATE_UPLOAD') {
                return response()->json(['status' => 'error', 'message' => 'Sorry, a file with the same name already exists.'], 422);
            }
            throw $exception;
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'The file '.$name.' has been uploaded successfully.',
            'data' => ['file' => $name, 'href' => '/forms/'.rawurlencode($name)],
        ]);
    }
}
