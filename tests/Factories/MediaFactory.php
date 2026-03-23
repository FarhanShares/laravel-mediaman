<?php

namespace FarhanShares\MediaMan\Tests\Factories;

use FarhanShares\MediaMan\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\FarhanShares\MediaMan\Models\Media>
     */
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'file_name' => 'file-name.png',
            'disk' => config('mediaman.disk'),
            'mime_type' => 'image/png',
            'size' => $this->faker->randomNumber(4),
        ];
    }
}
