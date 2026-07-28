<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        return view('programs.index', [
            'programs' => Program::query()
                ->where('is_active', true)
                ->orderBy('title')
                ->paginate(12),
        ]);
    }

    public function show(Program $program): View
    {
        abort_unless($program->is_active, 404);

        return view('programs.show', [
            'program' => $program->load([
                'schedules' => fn ($query) => $query->orderBy('day_of_week')->orderBy('starts_at'),
            ]),
        ]);
    }
}
