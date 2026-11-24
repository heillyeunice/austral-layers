<?php

namespace App\Http\Controllers;

use App\Models\QuoteInquiry;
use App\Notifications\NewQuoteInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffNotificationController extends Controller
{
    public function show(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();

        abort_unless(
            $notification->type === NewQuoteInquiry::class
                && isset($notification->data['inquiry_id'])
                && is_numeric($notification->data['inquiry_id']),
            404
        );

        $inquiry = QuoteInquiry::query()->findOrFail($notification->data['inquiry_id']);
        $notification->markAsRead();

        return redirect()->route('admin.inquiries.show', $inquiry);
    }
}
