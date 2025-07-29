<?php

namespace Database\Factories;

use App\Models\DoctorBlogPost;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DoctorBlogPostFactory extends Factory
{
    protected $model = DoctorBlogPost::class;

    public function definition(): array
    {
        $title = fake()->sentence(rand(3, 8));

        return [
            'doctor_id' => Doctor::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'featured_image' => fake()->optional()->imageUrl(),
            'short_description' => fake()->paragraph(),
            'content' => fake()->paragraphs(rand(5, 15), true),
            'is_published' => fake()->boolean(70),
            'published_at' => fake()->optional(0.7)->dateTimeThisMonth(),
            'seo_meta' => [
                'title' => fake()->optional()->sentence(4),
                'description' => fake()->optional()->sentence(),
                'keywords' => fake()->optional()->words(5, true),
            ],
            'views_count' => fake()->numberBetween(0, 1000),
            'created_at' => fake()->dateTimeThisMonth(),
            'updated_at' => fake()->dateTimeThisMonth(),
        ];
    }

    /**
     * Indicate that the blog post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => fake()->dateTimeThisMonth(),
        ]);
    }

    /**
     * Indicate that the blog post is unpublished.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
