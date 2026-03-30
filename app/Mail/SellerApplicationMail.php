<?php

namespace App\Mail;

use App\Models\Seller;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Seller $seller) {}

    public function build()
    {
        return $this->subject('🔔 New Seller Registration: ' . $this->seller->company_name)
            ->view('emails.seller_application');
    }
}
