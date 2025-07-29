<?php

namespace Database\Factories;

use App\Models\DoctorLandingPage;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorLandingPageFactory extends Factory
{
    protected $model = DoctorLandingPage::class;

    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'username' => fake()->unique()->userName(),
            'template' => fake()->randomElement(['modern', 'classic', 'minimal', 'professional']),
            'page_title' => fake()->sentence(3),
            'page_description' => fake()->paragraph(),
            'tagline' => fake()->sentence(6),
            'hero_image' => fake()->optional()->imageUrl(),
            'about_text' => fake()->paragraphs(3, true),
            'colors' => [
                'primary' => fake()->hexColor(),
                'secondary' => fake()->hexColor(),
                'accent' => fake()->hexColor(),
                'background' => '#ffffff',
                'text' => '#1f2937',
            ],
            'section_visibility' => [
                'hero' => fake()->boolean(90),
                'about' => fake()->boolean(85),
                'appointments' => fake()->boolean(95),
                'reviews' => fake()->boolean(80),
                'contact' => fake()->boolean(90),
            ],
            'is_published' => fake()->boolean(70),
            'custom_domain' => fake()->optional()->domainName(),
            'subdomain_enabled' => fake()->boolean(30),
            'seo_settings' => [
                'meta_keywords' => fake()->words(5, true),
                'meta_description' => fake()->sentence(),
                'og_title' => fake()->sentence(3),
                'og_description' => fake()->sentence(),
            ],
            'created_at' => fake()->dateTimeThisMonth(),
            'updated_at' => fake()->dateTimeThisMonth(),
        ];
    }

    /**
     * Indicate that the landing page is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    /**
     * Indicate that the landing page is unpublished.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
