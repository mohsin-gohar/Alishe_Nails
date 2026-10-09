<?php

namespace App\Jobs;

use App\Mail\AdminNewOrderMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public Order $order, public array $lineItems) {}

    public function handle(): void
    {
        $email = $this->order->email;

        if ($email) {
            Mail::to($email)->send(new OrderConfirmationMail($this->order));
        }

        Mail::to(config('services.admin.notification_email'))->send(new AdminNewOrderMail($this->order));
    }
}
