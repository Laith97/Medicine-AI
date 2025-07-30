<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LandingPageSectionTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Hero Section',
                'type' => 'hero',
                'category' => 'header',
                'description' => 'Main banner with title, subtitle, and call-to-action button',
                'default_config' => json_encode([
                    'title' => 'Welcome to My Practice',
                    'subtitle' => 'Providing quality healthcare with compassion',
                    'background_type' => 'gradient',
                    'background_color' => '#3b82f6',
                    'gradient_end' => '#10b981',
                    'text_color' => '#ffffff',
                    'button_text' => 'Book Appointment',
                    'button_link' => '#appointments',
                    'button_color' => '#ffffff',
                    'button_text_color' => '#3b82f6',
                    'animation' => 'fadeInUp',
                    'overlay_opacity' => 0.5,
                    'show_scroll_indicator' => true
                ]),
                'html_template' => '<section class="hero-section"><!-- Hero content --></section>',
                'css_template' => '.hero-section { min-height: 100vh; }',
                'preview_image' => '/images/sections/hero-preview.jpg',
                'is_premium' => false,
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'name' => 'About Section',
                'type' => 'about',
                'category' => 'content',
                'description' => 'Professional bio, credentials, and doctor information',
                'default_config' => json_encode([
                    'title' => 'About Dr. [Name]',
                    'content' => 'Your professional bio goes here...',
                    'image' => '',
                    'layout' => 'image-left',
                    'background_color' => '#ffffff',
                    'text_color' => '#374151',
                    'animation' => 'fadeInLeft',
                    'show_credentials' => true,
                    'show_cta' => false,
                    'cta_text' => 'Book Appointment',
                    'cta_link' => '#appointments'
                ]),
                'html_template' => '<section class="about-section"><!-- About content --></section>',
                'css_template' => '.about-section { padding: 5rem 0; }',
                'preview_image' => '/images/sections/about-preview.jpg',
                'is_premium' => false,
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'name' => 'Services Section',
                'type' => 'services',
                'category' => 'content',
                'description' => 'List of medical services offered with icons and descriptions',
                'default_config' => json_encode([
                    'title' => 'Our Services',
                    'subtitle' => 'Comprehensive healthcare solutions',
                    'services' => [
                        ['title' => 'General Consultation', 'description' => 'Comprehensive health checkups', 'icon' => 'fas fa-stethoscope'],
                        ['title' => 'Preventive Care', 'description' => 'Regular health screenings', 'icon' => 'fas fa-shield-alt'],
                        ['title' => 'Treatment Plans', 'description' => 'Personalized treatment approaches', 'icon' => 'fas fa-prescription-bottle-alt'],
                    ],
                    'layout' => 'grid-3',
                    'background_color' => '#f8fafc',
                    'text_color' => '#374151',
                    'animation' => 'fadeInUp',
                    'show_service_cta' => false,
                    'show_all_services_cta' => false
                ]),
                'html_template' => '<section class="services-section"><!-- Services content --></section>',
                'css_template' => '.services-section { padding: 5rem 0; }',
                'preview_image' => '/images/sections/services-preview.jpg',
                'is_premium' => false,
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'name' => 'Testimonials Section',
                'type' => 'testimonials',
                'category' => 'social-proof',
                'description' => 'Patient reviews and testimonials with ratings',
                'default_config' => json_encode([
                    'title' => 'What Patients Say',
                    'subtitle' => 'Real experiences from our patients',
                    'layout' => 'carousel',
                    'show_ratings' => true,
                    'background_color' => '#ffffff',
                    'text_color' => '#374151',
                    'animation' => 'fadeIn',
                    'show_cta' => false,
                    'cta_text' => 'Book Your Appointment',
                    'cta_link' => '#appointments'
                ]),
                'html_template' => '<section class="testimonials-section"><!-- Testimonials content --></section>',
                'css_template' => '.testimonials-section { padding: 5rem 0; }',
                'preview_image' => '/images/sections/testimonials-preview.jpg',
                'is_premium' => false,
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'name' => 'Contact Section',
                'type' => 'contact',
                'category' => 'footer',
                'description' => 'Contact information, form, and location details',
                'default_config' => json_encode([
                    'title' => 'Get In Touch',
                    'subtitle' => 'Contact us for appointments or inquiries',
                    'show_form' => true,
                    'show_map' => true,
                    'show_social' => false,
                    'show_emergency' => false,
                    'background_color' => '#f8fafc',
                    'text_color' => '#374151',
                    'animation' => 'fadeInUp',
                    'social_links' => [
                        'facebook' => '',
                        'twitter' => '',
                        'linkedin' => '',
                        'instagram' => ''
                    ],
                    'emergency_phone' => '911'
                ]),
                'html_template' => '<section class="contact-section"><!-- Contact content --></section>',
                'css_template' => '.contact-section { padding: 5rem 0; }',
                'preview_image' => '/images/sections/contact-preview.jpg',
                'is_premium' => false,
                'is_active' => true,
                'sort_order' => 5
            ],
            [
                'name' => 'Call to Action',
                'type' => 'cta',
                'category' => 'conversion',
                'description' => 'Prominent call-to-action section with buttons',
                'default_config' => json_encode([
                    'title' => 'Ready to Get Started?',
                    'subtitle' => 'Book your appointment today',
                    'button_text' => 'Book Now',
                    'button_link' => '#appointments',
                    'secondary_button_text' => 'Learn More',
                    'secondary_button_link' => '#about',
                    'background_type' => 'gradient',
                    'background_color' => '#3b82f6',
                    'gradient_end' => '#10b981',
                    'text_color' => '#ffffff',
                    'button_color' => '#ffffff',
                    'button_text_color' => '#3b82f6',
                    'animation' => 'pulse',
                    'show_features' => false,
                    'show_urgency' => false,
                    'show_contact_info' => false,
                    'show_scroll_indicator' => false
                ]),
                'html_template' => '<section class="cta-section"><!-- CTA content --></section>',
                'css_template' => '.cta-section { padding: 5rem 0; }',
                'preview_image' => '/images/sections/cta-preview.jpg',
                'is_premium' => false,
                'is_active' => true,
                'sort_order' => 6
            ],
            [
                'name' => 'Image Gallery',
                'type' => 'gallery',
                'category' => 'media',
                'description' => 'Photo gallery of clinic/practice with lightbox',
                'default_config' => json_encode([
                    'title' => 'Our Facility',
                    'subtitle' => 'Take a look at our modern clinic',
                    'images' => [],
                    'layout' => 'masonry',
                    'columns' => 3,
                    'background_color' => '#ffffff',
                    'text_color' => '#374151',
                    'animation' => 'zoomIn',
                    'show_load_more' => false
                ]),
                'html_template' => '<section class="gallery-section"><!-- Gallery content --></section>',
                'css_template' => '.gallery-section { padding: 5rem 0; }',
                'preview_image' => '/images/sections/gallery-preview.jpg',
                'is_premium' => true,
                'is_active' => true,
                'sort_order' => 7
            ],
            [
                'name' => 'FAQ Section',
                'type' => 'faq',
                'category' => 'content',
                'description' => 'Frequently asked questions with search functionality',
                'default_config' => json_encode([
                    'title' => 'Frequently Asked Questions',
                    'subtitle' => 'Common questions about our services',
                    'faqs' => [
                        ['question' => 'How do I book an appointment?', 'answer' => 'You can book online through our website or call our office directly.'],
                        ['question' => 'What insurance do you accept?', 'answer' => 'We accept most major insurance plans including Blue Cross, Aetna, and Medicare.'],
                        ['question' => 'What should I bring to my appointment?', 'answer' => 'Please bring your ID, insurance card, and any relevant medical records.']
                    ],
                    'layout' => 'accordion',
                    'background_color' => '#f8fafc',
                    'text_color' => '#374151',
                    'animation' => 'fadeInUp',
                    'show_search' => false,
                    'show_contact_cta' => false,
                    'show_categories' => false,
                    'contact_link' => '#contact',
                    'phone' => ''
                ]),
                'html_template' => '<section class="faq-section"><!-- FAQ content --></section>',
                'css_template' => '.faq-section { padding: 5rem 0; }',
                'preview_image' => '/images/sections/faq-preview.jpg',
                'is_premium' => true,
                'is_active' => true,
                'sort_order' => 8
            ]
        ];

        foreach ($templates as $template) {
            DB::table('landing_page_section_templates')->insert(array_merge($template, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }
}
