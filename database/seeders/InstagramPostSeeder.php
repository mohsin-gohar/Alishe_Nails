<?php

namespace Database\Seeders;

use App\Models\InstagramPost;
use Illuminate\Database\Seeder;

class InstagramPostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'instagram_id' => 'post_1788693396',
                'type' => 'IMAGE',
                'media_url' => '/images/products/rosy-quartz-ombre.jpg',
                'thumbnail_url' => null,
                'caption' => 'Handcrafted perfection ✨ Rosy Quartz Ombre with genuine gold leaf detailing. Reusable up to 5 times. #AlisheNails #PressOnNails',
                'permalink' => 'https://www.instagram.com/alishe_nails/',
                'posted_at' => now()->subDays(1),
                'is_active' => true,
            ],
            [
                'instagram_id' => 'post_1788694839',
                'type' => 'IMAGE',
                'media_url' => '/images/products/midnight-espresso.jpg',
                'thumbnail_url' => null,
                'caption' => 'Moody, elegant, bold. Midnight Espresso with metallic gold rim in sculpted coffin shape. 🤎 #NailsOfInstagram #LuxuryPressOns',
                'permalink' => 'https://www.instagram.com/alishe_nails/',
                'posted_at' => now()->subDays(2),
                'is_active' => true,
            ],
            [
                'instagram_id' => 'post_1788700748',
                'type' => 'IMAGE',
                'media_url' => '/images/products/golden-french.jpg',
                'thumbnail_url' => null,
                'caption' => 'Elevate your everyday manicure with our Golden French collection. High-gloss salon finish at home. ✨ #FrenchNails #AlisheNails',
                'permalink' => 'https://www.instagram.com/alishe_nails/',
                'posted_at' => now()->subDays(3),
                'is_active' => true,
            ],
            [
                'instagram_id' => 'post_1788701221',
                'type' => 'IMAGE',
                'media_url' => '/images/products/ivory-blossom-vine.jpg',
                'thumbnail_url' => null,
                'caption' => 'Hand-painted miniature botanical vines over velvety ivory white base. Pure romantic artistry. 🌿🤍 #CustomPressOns #NailArt',
                'permalink' => 'https://www.instagram.com/alishe_nails/',
                'posted_at' => now()->subDays(4),
                'is_active' => true,
            ],
            [
                'instagram_id' => 'post_1788695851',
                'type' => 'IMAGE',
                'media_url' => '/images/products/blush-aura.jpg',
                'thumbnail_url' => null,
                'caption' => 'Dreamy blush aura gradient with a velvety matte topcoat. Available in Almond & Coffin. 🌸 #AuraNails #MatteNails',
                'permalink' => 'https://www.instagram.com/alishe_nails/',
                'posted_at' => now()->subDays(5),
                'is_active' => true,
            ],
            [
                'instagram_id' => 'post_1788693359',
                'type' => 'IMAGE',
                'media_url' => '/images/products/pink-marble.jpg',
                'thumbnail_url' => null,
                'caption' => 'Rose quartz marble texture with delicate gold foil veins. Handmade with love for your special occasions. 💅✨',
                'permalink' => 'https://www.instagram.com/alishe_nails/',
                'posted_at' => now()->subDays(6),
                'is_active' => true,
            ],
            [
                'instagram_id' => 'post_1788693955',
                'type' => 'IMAGE',
                'media_url' => '/images/products/gilded-rose-dust.jpg',
                'thumbnail_url' => null,
                'caption' => 'Glitter and chrome that catches the light from every single angle. Sparkle in style with Gilded Rose Dust. 💖',
                'permalink' => 'https://www.instagram.com/alishe_nails/',
                'posted_at' => now()->subDays(7),
                'is_active' => true,
            ],
            [
                'instagram_id' => 'post_1788694706',
                'type' => 'IMAGE',
                'media_url' => '/images/products/modern-french-matte.jpg',
                'thumbnail_url' => null,
                'caption' => 'A clean modern take on the classic French manicure in soft matte mauve. Chic, timeless, effortless. #AlisheNails',
                'permalink' => 'https://www.instagram.com/alishe_nails/',
                'posted_at' => now()->subDays(8),
                'is_active' => true,
            ],
        ];

        foreach ($posts as $post) {
            InstagramPost::updateOrCreate(
                ['instagram_id' => $post['instagram_id']],
                $post
            );
        }
    }
}
