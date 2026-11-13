<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Alishe Nails charges a flat PKR 300 delivery fee across Karachi.
     *
     * Earlier builds seeded every Karachi zone with a zero fee and told the
     * customer that delivery would be "coordinated directly". The store now
     * shows a real, fixed delivery charge at cart and checkout time, so this
     * migration brings existing installations in line with the seeder.
     *
     * Only rows that are still free are touched: if the owner has already
     * configured a different fee from the admin, that value is left alone.
     */
    public function up(): void
    {
        DB::table('shipping_rates')
            ->where('city', 'Karachi')
            ->where('delivery_fee', 0)
            ->update(['delivery_fee' => 300, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Deliberately irreversible: making delivery free again is a business
        // decision, not something a rollback should silently do.
    }
};
