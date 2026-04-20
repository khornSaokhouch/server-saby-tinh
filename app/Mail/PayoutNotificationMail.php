<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PayoutNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $payouts;
    public string $storeName;
    public string $storeOwnerEmail;

    /**
     * @param \Illuminate\Support\Collection $payouts  Collection of Payout models (already loaded with relations)
     */
    public function __construct(Collection $payouts)
    {
        $this->payouts        = $payouts;
        $this->storeName      = $payouts->first()?->store?->name ?? 'Your Store';
        $this->storeOwnerEmail = $payouts->first()?->store?->user?->email ?? '';
    }

    public function build()
    {
        $count = $this->payouts->count();
        $subject = $count === 1
            ? '💰 Payout Processed — ' . $this->storeName
            : '💰 ' . $count . ' Payouts Processed — ' . $this->storeName;

        return $this
            ->subject($subject)
            ->view('emails.payout_notification');
    }
}
