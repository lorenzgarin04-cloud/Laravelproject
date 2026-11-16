<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /** Naghahatid ng product photos at avatars mula sa storage/app/public (hindi na kailangan ng storage:link). */
    public function show(string $path)
    {
        abort_unless(preg_match('#^(products|avatars)/[A-Za-z0-9._-]+$#', $path) === 1, 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, ['Cache-Control' => 'public, max-age=86400']);
    }
}
