<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\TemplateCategory;
use App\Models\TemplateVersion;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categories
        $birthdayCategory = TemplateCategory::firstOrCreate(
            ['slug' => 'birthday'],
            [
                'name' => 'Birthday Greetings',
                'icon' => 'cake',
                'is_active' => true,
            ]
        );

        TemplateCategory::firstOrCreate(
            ['slug' => 'anniversary'],
            [
                'name' => 'Anniversary & Romance',
                'icon' => 'favorite',
                'is_active' => true,
            ]
        );

        TemplateCategory::firstOrCreate(
            ['slug' => 'graduation'],
            [
                'name' => 'Graduation & Milestone',
                'icon' => 'school',
                'is_active' => true,
            ]
        );

        // 2. Template #001: Pink Luxury Digital Scrapbook
        $template001 = Template::firstOrCreate(
            ['code' => 'TMP-001'],
            [
                'category_id' => $birthdayCategory->id,
                'name' => 'Pink Luxury Digital Scrapbook',
                'description' => 'Feminine, elegant, and romantic digital birthday scrapbook featuring a 4-photo spread memory preview, dynamic polaroid album (up to 50 photos), video memories (up to 10 videos), and special letter.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1513151233558-d860c5398176?w=600&q=80',
                'is_premium' => false,
                'is_active' => true,
            ]
        );

        // 3. Template Version 1.0.0 (Flexible slots: 4-50 photos, 0-10 videos)
        TemplateVersion::updateOrCreate(
            [
                'template_id' => $template001->id,
                'version' => '1.0.0',
            ],
            [
                'schema_definition' => [
                    'theme' => 'pink_luxury_scrapbook',
                    'name' => 'Pink Luxury Digital Scrapbook',
                    'min_photos' => 4,
                    'max_photos' => 50,
                    'memory_preview_count' => 4,
                    'min_videos' => 0,
                    'max_videos' => 10,
                    'max_audio' => 1,
                    'slots' => [
                        'photos' => array_map(function ($i) {
                            return [
                                'slot_key' => 'photo_' . str_pad($i, 2, '0', STR_PAD_LEFT),
                                'label' => 'Photo ' . $i,
                                'is_required' => $i <= 4, // first 4 required for memory preview
                            ];
                        }, range(1, 50)),
                        'videos' => array_map(function ($i) {
                            return [
                                'slot_key' => 'video_' . str_pad($i, 2, '0', STR_PAD_LEFT),
                                'label' => 'Video ' . $i,
                                'is_required' => false,
                            ];
                        }, range(1, 10)),
                        'music' => [
                            [
                                'slot_key' => 'music_main',
                                'label' => 'Background Music',
                                'is_required' => false,
                            ]
                        ],
                    ],
                    'pages' => [
                        'splash',
                        'intro',
                        'greeting',
                        'memory_preview',
                        'memory_album',
                        'video_memories',
                        'special_letter',
                        'final_wishes'
                    ],
                    'default_text' => [
                        'recipient_name' => 'Nadia Aulia Fatimah',
                        'age' => 20,
                        'short_message' => 'Selamat ulang tahun yang ke-20. Semoga di usia yang baru ini kamu selalu diberikan kesehatan, kebahagiaan, dan keberhasilan.',
                        'letter_content' => "Dear Nadia,\n\nDi usia 20 tahun ini, semoga kamu menemukan lebih banyak alasan untuk tersenyum.\n\nSemoga setiap langkah yang kamu ambil membawa kamu semakin dekat dengan apa yang kamu impikan.\n\nTidak semua hari akan selalu mudah, tetapi semoga kamu selalu punya cukup keberanian untuk melewatinya.\n\nJangan lupa untuk menghargai dirimu sendiri, merayakan hal-hal kecil, dan menikmati setiap proses yang sedang kamu jalani.\n\nTerima kasih sudah menjadi Nadia yang selalu punya cerita untuk dikenang.\n\nSemoga tahun ini menjadi salah satu bab terbaik dalam hidupmu.\n\nWith love,\nFor Nadia Aulia Fatimah ❤️",
                        'closing_message' => "May this new chapter bring you\nbeautiful memories,\nunexpected happiness,\nand countless reasons to smile.\n\nKeep dreaming. Keep smiling. Keep being you. ❤️",
                    ],
                ],
                'is_published' => true,
            ]
        );
    }
}
