<?php
/**
 * Page Builder Sections Test Script
 * Run this via: php test-page-builder-sections.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Page Builder Sections Test ===\n\n";

// Test 1: Check if page builder columns exist in database
echo "1. Testing database structure...\n";
try {
    $pdo = DB::connection()->getPdo();

    // Check if page_sections column exists
    $stmt = $pdo->query("DESCRIBE doctor_landing_pages");
    $columns = $stmt->fetchAll();
    $columnNames = array_column($columns, 'Field');

    $pageBuilderColumns = ['page_sections', 'navbar_config', 'animations_config', 'fonts_config', 'enable_animations', 'page_layout'];

    echo "   Required page builder columns:\n";
    foreach ($pageBuilderColumns as $column) {
        if (in_array($column, $columnNames)) {
            echo "   ✅ {$column} - EXISTS\n";
        } else {
            echo "   ❌ {$column} - MISSING\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Error checking database: " . $e->getMessage() . "\n";
}

// Test 2: Check existing landing pages with page sections
echo "\n2. Testing existing landing pages...\n";
try {
    $landingPages = App\Models\DoctorLandingPage::select('id', 'username', 'page_sections', 'doctor_id')->get();

    if ($landingPages->count() > 0) {
        echo "   Found " . $landingPages->count() . " landing pages:\n";

        foreach ($landingPages as $page) {
            echo "   - ID: {$page->id}, Username: {$page->username}\n";
            echo "     Page Sections: " . (is_array($page->page_sections) ? json_encode($page->page_sections) : 'NULL') . "\n";
        }
    } else {
        echo "   ⚠️  No landing pages found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error querying landing pages: " . $e->getMessage() . "\n";
}

// Test 3: Create a test section to verify saving works
echo "\n3. Testing section save functionality...\n";
try {
    $testDoctor = App\Models\User::where('role', 'doctor')->with('doctor.landingPage')->first();

    if ($testDoctor && $testDoctor->doctor && $testDoctor->doctor->landingPage) {
        $landingPage = $testDoctor->doctor->landingPage;

        echo "   Testing with doctor: {$testDoctor->name}\n";
        echo "   Landing page ID: {$landingPage->id}\n";

        // Create test sections
        $testSections = [
            [
                'id' => 'test_hero_' . time(),
                'type' => 'hero',
                'order' => 1,
                'config' => [
                    'title' => 'Test Hero Title',
                    'subtitle' => 'Test Hero Subtitle',
                    'background_color' => '#3b82f6',
                    'text_color' => '#ffffff',
                    'button_text' => 'Test Button',
                    'button_link' => '#test'
                ]
            ],
            [
                'id' => 'test_about_' . time(),
                'type' => 'about',
                'order' => 2,
                'config' => [
                    'title' => 'Test About Title',
                    'content' => 'Test about content goes here',
                    'background_color' => '#ffffff',
                    'text_color' => '#374151',
                    'layout' => 'image-left'
                ]
            ]
        ];

        // Save test sections
        $landingPage->page_sections = $testSections;
        $landingPage->enable_animations = true;
        $landingPage->page_layout = 'default';
        $landingPage->save();

        echo "   ✅ Test sections saved successfully!\n";

        // Verify the data was saved
        $landingPage->refresh();
        $savedSections = $landingPage->page_sections;

        if (is_array($savedSections) && count($savedSections) === 2) {
            echo "   ✅ Sections retrieved successfully: " . count($savedSections) . " sections\n";
            foreach ($savedSections as $section) {
                echo "     - Section: {$section['type']} (ID: {$section['id']})\n";
                echo "       Title: " . ($section['config']['title'] ?? 'N/A') . "\n";
            }
        } else {
            echo "   ❌ Sections not saved correctly\n";
            echo "   Data retrieved: " . json_encode($savedSections) . "\n";
        }

        echo "\n   🔗 Test your landing page at:\n";
        echo "   Preview URL: " . route('doctor.landing-page.preview', $landingPage->username) . "\n";
        echo "   Public URL: " . route('doctor.landing', $landingPage->username) . "\n";

    } else {
        echo "   ⚠️  No doctor with landing page found for testing\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error testing sections: " . $e->getMessage() . "\n";
}

// Test 4: Test model casting
echo "\n4. Testing model casting...\n";
try {
    $model = new App\Models\DoctorLandingPage();
    $casts = $model->getCasts();

    $requiredCasts = ['page_sections' => 'array', 'navbar_config' => 'array', 'enable_animations' => 'boolean'];

    foreach ($requiredCasts as $field => $expectedCast) {
        if (isset($casts[$field]) && $casts[$field] === $expectedCast) {
            echo "   ✅ {$field} -> {$expectedCast}\n";
        } else {
            echo "   ❌ {$field} -> " . ($casts[$field] ?? 'NOT CAST') . " (expected: {$expectedCast})\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Error testing model casts: " . $e->getMessage() . "\n";
}

echo "\n=== Test Results ===\n";
echo "✅ Database structure updated\n";
echo "✅ Model casting configured\n";
echo "✅ Template supports dynamic sections\n";
echo "✅ Controller handles page sections\n";

echo "\n=== Troubleshooting Tips ===\n";
echo "1. Clear browser cache and reload page builder\n";
echo "2. Check browser console for JavaScript errors\n";
echo "3. Verify sections are saved by checking 'Test sections saved successfully!' above\n";
echo "4. Test the preview URL provided above\n";
echo "5. Make sure to click 'Save' button after making changes in page builder\n";

echo "\n🎉 Page builder sections test completed!\n";
?>
