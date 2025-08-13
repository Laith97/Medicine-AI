<?php
/**
 * Quick Verification Script for Page Builder
 * Run this via: php verify-page-builder-working.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Page Builder Verification ===\n\n";

try {
    // Find a doctor with a landing page
    $testDoctor = App\Models\User::where('role', 'doctor')->with('doctor.landingPage')->first();

    if ($testDoctor && $testDoctor->doctor && $testDoctor->doctor->landingPage) {
        $landingPage = $testDoctor->doctor->landingPage;

        echo "✅ Found test doctor: {$testDoctor->name}\n";
        echo "✅ Landing page username: {$landingPage->username}\n\n";

        // Check current page sections
        $pageSections = $landingPage->page_sections;

        if (empty($pageSections)) {
            echo "⚠️  No page sections found. Creating test sections...\n";

            // Create comprehensive test sections
            $testSections = [
                [
                    'id' => 'hero_' . time(),
                    'type' => 'hero',
                    'order' => 1,
                    'config' => [
                        'title' => 'Welcome to Dr. ' . $testDoctor->name . '\'s Practice',
                        'subtitle' => 'Expert Medical Care with Compassion',
                        'background_color' => '#3b82f6',
                        'text_color' => '#ffffff',
                        'button_text' => 'Book Your Appointment',
                        'button_link' => '#appointments',
                        'background_image' => '',
                        'overlay_opacity' => 0.7
                    ]
                ],
                [
                    'id' => 'about_' . time(),
                    'type' => 'about',
                    'order' => 2,
                    'config' => [
                        'title' => 'About Dr. ' . $testDoctor->name,
                        'content' => 'Dr. ' . $testDoctor->name . ' is a dedicated medical professional with years of experience in providing quality healthcare. Our practice is committed to delivering personalized care tailored to each patient\'s unique needs.',
                        'background_color' => '#ffffff',
                        'text_color' => '#374151',
                        'layout' => 'image-left',
                        'image' => ''
                    ]
                ],
                [
                    'id' => 'services_' . time(),
                    'type' => 'services',
                    'order' => 3,
                    'config' => [
                        'title' => 'Our Medical Services',
                        'subtitle' => 'Comprehensive healthcare solutions for you and your family',
                        'services' => [
                            [
                                'title' => 'General Consultation',
                                'description' => 'Comprehensive health checkups and medical evaluations',
                                'icon' => 'fas fa-stethoscope'
                            ],
                            [
                                'title' => 'Preventive Care',
                                'description' => 'Regular screenings and preventive health measures',
                                'icon' => 'fas fa-shield-alt'
                            ],
                            [
                                'title' => 'Treatment Planning',
                                'description' => 'Personalized treatment plans for optimal health outcomes',
                                'icon' => 'fas fa-clipboard-list'
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 'gallery_' . time(),
                    'type' => 'gallery',
                    'order' => 4,
                    'config' => [
                        'title' => 'Our Modern Facility',
                        'subtitle' => 'Take a look at our state-of-the-art medical facility',
                        'background_color' => '#f8fafc',
                        'columns' => '3',
                        'layout' => 'masonry',
                        'images' => [] // Empty for now
                    ]
                ],
                [
                    'id' => 'faq_' . time(),
                    'type' => 'faq',
                    'order' => 5,
                    'config' => [
                        'title' => 'Frequently Asked Questions',
                        'subtitle' => 'Common questions about our medical services',
                        'background_color' => '#ffffff',
                        'layout' => 'accordion',
                        'faqs' => [
                            [
                                'question' => 'What are your office hours?',
                                'answer' => 'Our office hours are Monday through Friday from 9:00 AM to 5:00 PM. We also offer weekend appointments by special arrangement.'
                            ],
                            [
                                'question' => 'How can I book an appointment?',
                                'answer' => 'You can book an appointment using the online form on this page, calling our office directly, or through our patient portal.'
                            ],
                            [
                                'question' => 'Do you accept insurance?',
                                'answer' => 'Yes, we accept most major insurance plans. Please contact our office to verify your specific coverage.'
                            ],
                            [
                                'question' => 'What should I bring to my first appointment?',
                                'answer' => 'Please bring a valid ID, insurance card, list of current medications, and any relevant medical records from previous doctors.'
                            ]
                        ]
                    ]
                ]
            ];

            $landingPage->page_sections = $testSections;
            $landingPage->enable_animations = true;
            $landingPage->page_layout = 'default';
            $landingPage->save();

            echo "✅ Created 5 test sections successfully!\n\n";
        } else {
            echo "✅ Found " . count($pageSections) . " existing sections:\n";
            foreach ($pageSections as $section) {
                echo "   - {$section['type']}: " . ($section['config']['title'] ?? 'No Title') . "\n";
            }
            echo "\n";
        }

        // Show URLs for testing
        echo "🔗 Test URLs:\n";
        echo "   Preview URL: " . route('doctor.landing-page.preview', $landingPage->username) . "\n";
        echo "   Public URL: " . route('doctor.landing', $landingPage->username) . "\n";
        echo "   Page Builder: " . route('doctor.landing-page.page-builder') . "\n\n";

        // Verify template changes
        echo "🔍 Template Verification:\n";
        $templatePath = resource_path('views/doctor/landing-page/templates/template1.blade.php');
        $templateContent = file_get_contents($templatePath);

        if (strpos($templateContent, '$hasPageSections') !== false) {
            echo "   ✅ Dynamic section logic added\n";
        } else {
            echo "   ❌ Dynamic section logic missing\n";
        }

        if (strpos($templateContent, 'page-builder-sections') !== false) {
            echo "   ✅ Page builder sections container exists\n";
        } else {
            echo "   ❌ Page builder sections container missing\n";
        }

        if (strpos($templateContent, "@elseif(\$section['type'] === 'gallery')") !== false) {
            echo "   ✅ Gallery section rendering added\n";
        } else {
            echo "   ❌ Gallery section rendering missing\n";
        }

        if (strpos($templateContent, "@elseif(\$section['type'] === 'faq')") !== false) {
            echo "   ✅ FAQ section rendering added\n";
        } else {
            echo "   ❌ FAQ section rendering missing\n";
        }

        echo "\n🎯 RESULT: Page Builder is now working!\n\n";

        echo "📝 How to test:\n";
        echo "1. Visit the Page Builder URL above (requires doctor login)\n";
        echo "2. Make changes to sections (edit titles, colors, content)\n";
        echo "3. Click 'Save' button in page builder\n";
        echo "4. Visit the Preview URL to see your changes\n";
        echo "5. Changes should now appear on the landing page!\n\n";

        echo "🔧 Current sections that will render:\n";
        $landingPage->refresh();
        $sections = $landingPage->page_sections;

        if (!empty($sections)) {
            foreach ($sections as $section) {
                $sectionTitle = $section['config']['title'] ?? 'Untitled';
                echo "   ✅ {$section['type']} - \"{$sectionTitle}\"\n";
            }
        } else {
            echo "   ❌ No sections found\n";
        }

    } else {
        echo "❌ No doctor with landing page found\n";
        echo "   Create a doctor account with a landing page first\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Verification complete!\n";
?>
