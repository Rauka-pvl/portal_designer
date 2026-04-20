<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PublicFileStorage
{
    public static function store(UploadedFile $file, string $directory): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalName = trim((string) $originalName);
        $safeBaseName = preg_replace('/[^\pL\pN]+/u', '_', $originalName) ?? '';
        $safeBaseName = trim($safeBaseName, '._-');

        if ($safeBaseName === '') {
            $safeBaseName = 'file';
        }

        $extension = trim((string) ($file->getClientOriginalExtension() ?: $file->extension()));
        $timestamp = now()->format('Ymd_His_u');
        $fileName = $safeBaseName.'_'.$timestamp;

        if ($extension !== '') {
            $fileName .= '.'.Str::lower($extension);
        }

        return $file->storeAs($directory, $fileName, 'public');
    }
}
