<?php

namespace App\Mail;

use App\Models\Seller;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Seller $seller,
        public string $status,
        public string $message_title,
        public string $message_content,
        public string $status_color = '#4f46e5'
    ) {}

    public function build()
    {
        $subject = $this->status === 'approved' 
            ? '✅ Seller Account Approved' 
            : '❌ Seller Registration Rejected';

        return $this->subject($subject)
            ->view('emails.seller_status');
    }
}
