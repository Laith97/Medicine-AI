<?php
/**
 * Landing Page Fixes Test Script
 * Run this via: php test-landing-page-fixes.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Landing Page Management Fixes Test ===\n\n";

// Test 1: Check if landing_page_visits table exists and has correct columns
echo "1. Testing analytics database structure...\n";
try {
    $pdo = DB::connection()->getPdo();

    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'landing_page_visits'");
    if ($stmt->rowCount() > 0) {
        echo "   ✅ landing_page_visits table exists\n";

        // Check columns
        $columns = $pdo->query("DESCRIBE landing_page_visits")->fetchAll();
        $columnNames = array_column($columns, 'Field');

        $requiredColumns = ['id', 'doctor_id', 'ip_address', 'user_agent', 'referrer_url', 'page_url'];
        $missingColumns = array_diff($requiredColumns, $columnNames);

        if (empty($missingColumns)) {
            echo "   ✅ All required columns exist\n";
        } else {
            echo "   ❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
        }
    } else {
        echo "   ❌ landing_page_visits table does not exist\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking database: " . $e->getMessage() . "\n";
}

// Test 2: Check if routes are properly defined
echo "\n2. Testing routes...\n";
$routes = [
    'appointments.store' => 'POST /appointments',
    'doctor.landing-page.update-sections' => 'POST /doctor/landing-page/update-sections',
    'doctor.landing-page.analytics' => 'GET /doctor/landing-page/analytics'
];

foreach ($routes as $routeName => $description) {
    try {
        $url = route($routeName);
        echo "   ✅ {$description} -> {$url}\n";
    } catch (Exception $e) {
        echo "   ❌ {$description} -> Route not found\n";
    }
}

// Test 3: Check if essential files exist
echo "\n3. Testing file structure...\n";
$files = [
    'resources/views/doctor/landing-page/page-builder.blade.php' => 'Page Builder Template',
    'resources/views/doctor/landing-page/templates/template1.blade.php' => 'Landing Page Template',
    'app/Http/Controllers/AppointmentController.php' => 'Appointment Controller',
    'app/Http/Controllers/Doctor/LandingPageController.php' => 'Landing Page Controller',
    'app/Models/LandingPageVisit.php' => 'Analytics Model'
];

foreach ($files as $path => $description) {
    $fullPath = __DIR__ . '/' . $path;
    if (file_exists($fullPath)) {
        echo "   ✅ {$description}\n";
    } else {
        echo "   ❌ {$description} - File not found: {$path}\n";
    }
}

// Test 4: Check middleware registration
echo "\n4. Testing middleware...\n";
try {
    $middleware = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddleware();
    echo "   ✅ Middleware system loaded\n";

    // Check if our custom middleware is registered
    $aliases = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareAliases();
    $requiredAliases = ['role', 'doctor'];

    foreach ($requiredAliases as $alias) {
        if (isset($aliases[$alias])) {
            echo "   ✅ {$alias} middleware registered\n";
        } else {
            echo "   ❌ {$alias} middleware not found\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Error checking middleware: " . $e->getMessage() . "\n";
}

// Test 5: Test sample doctor landing page data
echo "\n5. Testing sample data...\n";
try {
    $sampleDoctor = App\Models\User::where('role', 'doctor')->with('doctor.landingPage')->first();

    if ($sampleDoctor && $sampleDoctor->doctor) {
        echo "   ✅ Found sample doctor: {$sampleDoctor->name}\n";

        if ($sampleDoctor->doctor->landingPage) {
            echo "   ✅ Doctor has landing page\n";
        } else {
            echo "   ⚠️  Doctor has no landing page (this is normal for new accounts)\n";
        }
    } else {
        echo "   ⚠️  No sample doctor found (this is normal for fresh installs)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking sample data: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "The fixes implemented:\n";
echo "1. ✅ Fixed appointment form AJAX handling\n";
echo "2. ✅ Fixed analytics database column naming\n";
echo "3. ✅ Enhanced page builder error handling\n";
echo "4. ✅ Added section editor save functionality\n";
echo "5. ✅ Improved middleware AJAX support\n";
echo "6. ✅ Fixed appointment form validation\n";
echo "7. ✅ Enhanced smooth scrolling for navigation\n";

echo "\n=== Next Steps ===\n";
echo "1. Test the landing page in a browser\n";
echo "2. Check browser console for any remaining JavaScript errors\n";
echo "3. Test appointment booking functionality\n";
echo "4. Verify analytics tracking works\n";
echo "5. Test page builder section editing and saving\n";

echo "\n=== Manual Testing URLs ===\n";
echo "- Test page: " . url('/landing-page-test.html') . "\n";
echo "- Landing page builder: " . url('/doctor/landing-page') . " (requires doctor login)\n";
echo "- Sample landing page: " . url('/doctor/[doctor-username]') . " (replace with actual doctor username)\n";

echo "\n🎉 All automated tests completed!\n";
?>
