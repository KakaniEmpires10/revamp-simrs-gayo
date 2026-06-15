<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\FeedbackPreferenceUpdateRequest;
use App\Models\Settings\UserSetting;
use App\Support\Feedback;
use Illuminate\Http\RedirectResponse;

class FeedbackPreferenceController extends Controller
{
    public function update(FeedbackPreferenceUpdateRequest $request): RedirectResponse
    {
        return Feedback::mutasi(
            fn (): mixed => UserSetting::setFeedbackMode(
                $request->user()->getAuthIdentifier(),
                $request->validated('feedback_mode'),
            ),
            __('Preferensi feedback diperbarui.'),
            __('Preferensi feedback gagal diperbarui.'),
        );
    }
}
