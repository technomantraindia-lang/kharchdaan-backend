<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    public function upload(UploadedFile $file, string $folder = 'products'): string
    {
        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $name, 'public');

        // Also mirror to public/media/$folder for zero-latency direct web server delivery
        try {
            $destDir = public_path('media/' . $folder);
            if (! is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            $storedFile = storage_path('app/public/' . $path);
            if (file_exists($storedFile)) {
                @copy($storedFile, $destDir . '/' . $name);
            }
        } catch (\Throwable $e) {
            // Non-blocking mirror copy
        }

        return $path;
    }

    public function uploadMany(array $files, string $folder = 'products/gallery'): array
    {
        $paths = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $paths[] = $this->upload($file, $folder);
            }
        }

        return $paths;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            $publicPath = public_path('media/' . $path);
            if (file_exists($publicPath)) {
                @unlink($publicPath);
            }
        }
    }
}
