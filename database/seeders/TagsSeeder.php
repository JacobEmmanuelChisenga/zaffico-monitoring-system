<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use App\Models\Tags;

class TagsSeeder extends Seeder
{
    public function run()
    {
        $tags = [
            ['name' => 'Production', 'color' => '#FF0000'],
            ['name' => 'Development', 'color' => '#00FF00'],
            ['name' => 'Staging', 'color' => '#0000FF'],
            ['name' => 'Critical', 'color' => '#FFA500']
        ];

        foreach ($tags as $tag) {
            Tags::create($tag);
        }
    }
}
