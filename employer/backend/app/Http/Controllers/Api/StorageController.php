<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class StorageController extends Controller
{
    public function show($path)
    {
        $path = ltrim($path, '/');

        if (! Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $fullPath = Storage::disk('public')->path($path);
        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';

        return response()->file($fullPath, ['Content-Type' => $mime]);
    }
}
