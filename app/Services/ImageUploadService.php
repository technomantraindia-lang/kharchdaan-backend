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
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
