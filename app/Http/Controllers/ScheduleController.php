<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __invoke(): View
    {
        return view('schedule.index', [
            'schedules' => Schedule::query()
                ->with('program')
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->get()
                ->groupBy('day_of_week'),
        ]);
    }
}
