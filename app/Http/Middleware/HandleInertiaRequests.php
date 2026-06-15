<?php

namespace App\Http\Middleware;

use App\Models\Settings\AppSetting;
use App\Models\Settings\UserSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'platform' => $this->detectPlatform($request),
            'feedbackMode' => fn () => UserSetting::feedbackMode($request->user()?->getAuthIdentifier()),
            'pemeriksaanNavigationMode' => fn () => UserSetting::pemeriksaanNavigationMode($request->user()?->getAuthIdentifier()),
            'bpjsAntrolTaskIdEnabled' => fn () => AppSetting::bpjsAntrolTaskIdEnabled(),
            'flash' => [
                'toast' => fn () => $this->toastFeedback($request),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private function detectPlatform(Request $request): string
    {
        $userAgent = $request->userAgent() ?? '';
        $isTouchDevice = preg_match('/Mobile|Android|iPhone|iPad|Tablet/i', $userAgent) === 1;

        return $isTouchDevice ? 'touch' : 'desktop';
    }

    /**
     * @return array{type:string,message:string}|null
     */
    private function toastFeedback(Request $request): ?array
    {
        $toast = $request->session()->get('toast');

        if (is_array($toast) && isset($toast['type'], $toast['message'])) {
            return [
                'type' => (string) $toast['type'],
                'message' => (string) $toast['message'],
            ];
        }

        foreach (['success', 'error', 'warning', 'info'] as $type) {
            if ($request->session()->has($type)) {
                return [
                    'type' => $type,
                    'message' => (string) $request->session()->get($type),
                ];
            }
        }

        if ($request->session()->has('message')) {
            return [
                'type' => (string) $request->session()->get('type', 'info'),
                'message' => (string) $request->session()->get('message'),
            ];
        }

        return null;
    }
}
