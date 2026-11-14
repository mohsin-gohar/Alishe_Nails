<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "REVIEWS:\n";
foreach (Review::orderByDesc('id')->take(10)->get() as $r) {
    echo "  #{$r->id} product={$r->product_id} approved=".($r->is_approved ? '1' : '0')." name={$r->customer_name}\n";
}

echo "ORDERS:\n";
foreach (Order::orderByDesc('id')->take(8)->get() as $o) {
    echo "  #{$o->id} {$o->order_number} status={$o->status} payment={$o->payment_status} total={$o->total}\n";
}

echo "CATEGORIES:\n";
foreach (Category::orderBy('id')->get() as $c) {
    echo "  #{$c->id} {$c->name} (slug={$c->slug})\n";
}

echo "HOMEPAGE:\n";
foreach (Product::where('is_homepage', true)->orderBy('homepage_order')->get() as $p) {
    echo "  #{$p->id} {$p->name} order={$p->homepage_order}\n";
}
