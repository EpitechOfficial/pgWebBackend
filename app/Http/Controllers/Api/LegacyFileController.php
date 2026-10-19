<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LegacyFileController extends Controller
{
    public function externalLinks(): JsonResponse { return $this->read('external-links.json'); }
    public function sectionalHeads(): JsonResponse { return $this->read('sectional-heads.json'); }
    public function updateExternalLinks(Request $request): JsonResponse { return $this->write($request, 'external-links.json'); }
    public function updateSectionalHeads(Request $request): JsonResponse { return $this->write($request, 'sectional-heads.json'); }

    private function path(string $file): string
    {
        return storage_path('app/data/'.$file);
    }

    private function read(string $file): JsonResponse
    {
        $path = $this->path($file);
        if (!is_file($path)) return response()->json(['status' => 'error', 'message' => 'Data file not found.'], 404);
        $data = json_decode((string) file_get_contents($path), true);
        return response()->json(['status' => 'ok', 'data' => $data]);
    }

    private function write(Request $request, string $file): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) return response()->json(['status' => 'error', 'message' => 'Invalid JSON.'], 400);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false || file_put_contents($this->path($file), $json, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to save data file.');
        }
        return response()->json(['status' => 'ok']);
    }
}
