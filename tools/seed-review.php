<?php

/**
 * Creates one pending review so the admin moderation flow can be tested.
 * Prints the review id on success.
 */

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Clear leftovers from a previous run so the moderation queue stays tidy.
App\Models\Review::where('comment', 'like', 'AUDIT-PENDING-REVIEW%')->delete();

$review = App\Models\Review::create([
    'product_id' => App\Models\Product::query()->value('id'),
    'user_id' => null,
    'customer_name' => 'Audit Reviewer',
    'rating' => 4,
    'comment' => 'AUDIT-PENDING-REVIEW — this review was created by the automated admin flow test.',
    'is_approved' => false,
    'is_verified_purchase' => false,
]);

echo $review->id.PHP_EOL;
