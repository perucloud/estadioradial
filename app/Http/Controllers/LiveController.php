<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use Illuminate\View\View;

class LiveController extends Controller
{
    public function __invoke(): View
    {
        return view('live.index', [
            'audioStream' => $this->stream('audio'),
            'videoStream' => $this->stream('video'),
        ]);
    }

    private function stream(string $type): ?Stream
    {
        return Stream::query()
            ->with('media')
            ->where('type', $type)
            ->orderByDesc('is_active')
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->first();
    }
}
