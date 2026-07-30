<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDefaultLocationRequest;
use App\Models\ActivityLog;
use App\Models\Advertisement;
use App\Models\Location;
use App\Models\PortalSetting;
use App\Models\Post;
use App\Models\Program;
use App\Models\Stream;
use App\Models\User;
use App\Support\DefaultLocationSettings;
use App\Support\SchedulerHealth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        SchedulerHealth $schedulerHealth,
        DefaultLocationSettings $defaultLocations,
    ): View {
        $oldSelection = collect(['country', 'region', 'province', 'district'])
            ->mapWithKeys(function (string $type): array {
                $value = session()->getOldInput('default_location_'.$type.'_id');

                return [$type => is_numeric($value) ? (int) $value : null];
            })
            ->filter()
            ->all();
        $defaultLocationSelection = $oldSelection + $defaultLocations->selection();
        $selectedLocationId = collect($defaultLocationSelection)->last();
        $editorialIdentity = $defaultLocations->editorialIdentity();

        return view('admin.dashboard', [
            'metrics' => [
                'Noticias' => Post::query()->count(),
                'Publicadas' => Post::query()->where('status', 'published')->count(),
                'Programas' => Program::query()->count(),
                'Usuarios' => User::query()->where('is_active', true)->count(),
            ],
            'streams' => Stream::query()->where('is_active', true)->count(),
            'advertisements' => Advertisement::query()->where('is_active', true)->count(),
            'recentPosts' => Post::query()->latest()->limit(5)->get(),
            'scheduler' => $schedulerHealth->snapshot(),
            'overdueScheduledPosts' => Post::query()
                ->where('status', 'scheduled')
                ->whereNotNull('scheduled_for')
                ->where('scheduled_for', '<=', now())
                ->count(),
            'defaultLocationSelection' => $defaultLocationSelection,
            'defaultLocationOptions' => $defaultLocations->options($defaultLocationSelection),
            'defaultLocationLabel' => $selectedLocationId
                ? Location::query()->find($selectedLocationId)?->fullName()
                : 'Sin ubicación predeterminada',
            'locationOptionsUrl' => route('admin.locations.options'),
            'editorialIdentity' => $editorialIdentity,
        ]);
    }

    public function updateDefaultLocation(
        UpdateDefaultLocationRequest $request,
        DefaultLocationSettings $defaultLocations,
    ): RedirectResponse {
        $selection = $defaultLocations->normalize(array_filter(
            $request->selection(),
            fn ($value) => $value !== null,
        ));
        $setting = PortalSetting::put(
            DefaultLocationSettings::SETTING_KEY,
            $selection,
            'site',
            false,
        );
        PortalSetting::put(
            DefaultLocationSettings::BADGE_SETTING_KEY,
            [
                'enabled' => $request->boolean('editorial_badge_enabled'),
                'label' => trim((string) $request->input('editorial_badge_label')) ?: null,
            ],
            'site',
            true,
        );

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'settings.default_location_updated',
            'subject_type' => $setting->getMorphClass(),
            'subject_id' => $setting->id,
            'properties' => [
                'selection' => $selection,
                'editorial_badge_enabled' => $request->boolean('editorial_badge_enabled'),
            ],
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);

        return back()->with('status', 'Ubicación predeterminada actualizada.');
    }
}
