<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

final class CmsUploadService
{
    public function imageDirectory(): string
    {
        return base_path('../frontend/public/image');
    }

    public function formsDirectory(): string
    {
        return base_path('../frontend/public/forms');
    }

    public function safeName(UploadedFile $file, string $fallback): string
    {
        return preg_replace('/[^A-Za-z0-9._-]/', '-', trim($file->getClientOriginalName()))
            ?: $fallback.'-'.time().'.'.$file->getClientOriginalExtension();
    }

    public function store(UploadedFile $file, string $directory, string $name, bool $rejectDuplicate = false): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to prepare the upload directory.');
        }
        if ($rejectDuplicate && is_file($directory.DIRECTORY_SEPARATOR.$name)) {
            throw new RuntimeException('DUPLICATE_UPLOAD');
        }
        $file->move($directory, $name);
    }

    public function delete(string $directory, string $name): void
    {
        $path = $directory.DIRECTORY_SEPARATOR.basename($name);
        if ($name !== '' && is_file($path)) @unlink($path);
    }
}
