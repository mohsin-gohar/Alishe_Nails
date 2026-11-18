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
use Illuminate\Support\Facades\Log;
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

        // The admin notification address is optional configuration. Sending
        // to null throws, which would fail the job after the customer mail
        // already went out and cause duplicate emails on every retry.
        $adminEmail = config('services.admin.notification_email');

        if (is_string($adminEmail) && $adminEmail !== '') {
            Mail::to($adminEmail)->send(new AdminNewOrderMail($this->order));
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::critical("SendOrderEmails permanently failed for Order #{$this->order->order_number}: ".($exception?->getMessage() ?? 'Unknown error'));
    }
}
