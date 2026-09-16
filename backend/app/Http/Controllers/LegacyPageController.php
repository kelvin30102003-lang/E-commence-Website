<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyPageController extends Controller
{
    private const ALLOWED_DIRECTORIES = [
        'Admin',
        'Auth',
        'Users',
    ];

    private const CLEAN_ROUTES = [
        'home' => 'Users/Home.php',
        'shop' => 'Users/shop.php',
        'contact' => 'Users/contactUs.php',
        'about' => 'Users/aboutUs.php',
        'cart' => 'Users/cart.php',
        'checkout' => 'Users/checkout.php',
        'payment' => 'Users/payment.php',
        'profile' => 'Users/profile.php',
        'track' => 'Users/track.php',
        'admin' => 'Admin/adminDashboard.php',
        'admin/login' => 'Admin/adminLogin.php',
    ];

    public function clean(Request $request, string $page = 'home'): Response
    {
        abort_unless(isset(self::CLEAN_ROUTES[$page]), 404);

        return $this->render($request, self::CLEAN_ROUTES[$page]);
    }

    public function rootPhp(Request $request, string $file): Response
    {
        return $this->render($request, 'Users/' . $file . '.php');
    }

    public function namespacedPhp(Request $request, string $directory, string $file): Response
    {
        abort_unless(in_array($directory, self::ALLOWED_DIRECTORIES, true), 404);

        return $this->render($request, $directory . '/' . $file . '.php');
    }

    private function render(Request $request, string $relativePath): Response
    {
        $projectRoot = dirname(base_path());
        $path = $this->resolveLegacyPath($projectRoot, $relativePath);

        abort_unless(is_file($path), 404);

        $previousDirectory = getcwd();
        $legacyDirectory = dirname($path);
        $scriptName = '/' . str_replace('\\', '/', $relativePath);

        $_GET = $request->query->all();
        $_POST = $request->request->all();
        $_COOKIE = $request->cookies->all();
        $_FILES = $this->normalizeUploadedFiles($request->files->all());
        $_SERVER['SCRIPT_FILENAME'] = $path;
        $_SERVER['SCRIPT_NAME'] = $scriptName;
        $_SERVER['PHP_SELF'] = $scriptName;
        $_SERVER['REQUEST_URI'] = $request->getRequestUri();
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['QUERY_STRING'] = $request->getQueryString() ?? '';

        chdir($legacyDirectory);
        ob_start();

        try {
            require $path;
        } finally {
            if ($previousDirectory !== false) {
                chdir($previousDirectory);
            }
        }

        $content = ob_get_clean();
        $status = http_response_code() ?: 200;
        http_response_code(200);

        return response($content === false ? '' : $content, $status);
    }

    private function resolveLegacyPath(string $projectRoot, string $relativePath): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        abort_if(str_contains($normalized, '..'), 404);

        return $projectRoot . DIRECTORY_SEPARATOR . $normalized;
    }

    private function normalizeUploadedFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $normalized[$key] = $this->normalizeUploadedFiles($file);
                continue;
            }

            if ($file === null) {
                continue;
            }

            $normalized[$key] = [
                'name' => $file->getClientOriginalName(),
                'type' => $file->getClientMimeType(),
                'tmp_name' => $file->getPathname(),
                'error' => $file->getError(),
                'size' => $file->getSize(),
            ];
        }

        return $normalized;
    }
}
