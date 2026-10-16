<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

/** Serves the one stylesheet, with a long cache. No other file can be fetched. */
class AssetController
{
    private const FILES = [
        'data-rights.css' => 'text/css; charset=UTF-8',
    ];

    public function __invoke(string $file): Response
    {
        abort_unless(array_key_exists($file, self::FILES), 404);

        $path = __DIR__.'/../../../resources/dist/'.$file;

        return response((string) file_get_contents($path), 200, [
            'Content-Type' => self::FILES[$file],
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
