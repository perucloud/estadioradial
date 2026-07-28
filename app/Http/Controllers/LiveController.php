<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use Illuminate\View\View;

class LiveController extends Controller
{
    public function __invoke(): View
    {
        return view('live.index', [
            'audioStream' => Stream::query()
                ->where('type', 'audio')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first(),
            'videoStream' => Stream::query()
                ->where('type', 'video')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first(),
        ]);
    }
}
