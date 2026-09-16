<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegacyAssetController extends Controller
{
    private const ALLOWED_ROOTS = [
        'Assect',
        'uploads',
    ];

    public function show(Request $request, string $root, string $path): BinaryFileResponse
    {
        abort_unless(in_array($root, self::ALLOWED_ROOTS, true), 404);

        $projectRoot = dirname(base_path());
        $rootPath = realpath($projectRoot . DIRECTORY_SEPARATOR . $root);
        abort_unless(is_string($rootPath), 404);

        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        abort_if(str_contains($normalizedPath, '..'), 404);

        $filePath = realpath($rootPath . DIRECTORY_SEPARATOR . $normalizedPath);
        abort_unless(is_string($filePath) && str_starts_with($filePath, $rootPath) && is_file($filePath), 404);

        return response()->file($filePath);
    }
}
