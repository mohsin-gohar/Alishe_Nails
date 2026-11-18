<?php

namespace Tests\Feature;

use App\Jobs\SendOrderEmails;
use App\Mail\AdminNewOrderMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderEmailTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        return Order::create([
            'first_name' => 'Sana',
            'last_name' => 'Malik',
            'email' => 'sana@example.com',
            'phone' => '03001234567',
            'address' => '123 Blossom Lane',
            'city' => 'Karachi',
            'payment_method' => 'jazzcash',
            'subtotal' => 1000,
            'shipping' => 300,
            'total' => 1300,
            'status' => 'pending',
        ]);
    }

    public function test_job_sends_the_customer_mail_and_skips_an_unset_admin_address(): void
    {
        Mail::fake();
        config(['services.admin.notification_email' => null]);

        (new SendOrderEmails($this->makeOrder(), []))->handle();

        Mail::assertSent(OrderConfirmationMail::class, 1);
        Mail::assertNotSent(AdminNewOrderMail::class);
    }

    public function test_job_sends_both_mails_when_an_admin_address_is_configured(): void
    {
        Mail::fake();
        config(['services.admin.notification_email' => 'ops@example.com']);

        (new SendOrderEmails($this->makeOrder(), []))->handle();

        Mail::assertSent(OrderConfirmationMail::class, 1);
        Mail::assertSent(AdminNewOrderMail::class, fn (AdminNewOrderMail $mail) => $mail->hasTo('ops@example.com'));
    }
}
