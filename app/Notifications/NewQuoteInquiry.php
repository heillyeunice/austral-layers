<?php

namespace App\Notifications;

use App\Models\QuoteInquiry;
use Illuminate\Notifications\Notification;

class NewQuoteInquiry extends Notification
{
    public function __construct(private readonly QuoteInquiry $inquiry) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'inquiry_id' => $this->inquiry->getKey(),
            'customer_name' => $this->inquiry->name,
            'service' => $this->inquiry->service,
        ];
    }
}
