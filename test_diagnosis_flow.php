<?php

/**
 * Test script to verify the new diagnosis flow implementation
 * This script tests the separation of AI analysis and manual diagnosis
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Diagnosis;
use App\Models\AiAssistantResult;

echo "=== Testing New Diagnosis Flow Implementation ===\n\n";

// Test 1: Check if models have correct relationships
echo "1. Testing Model Relationships:\n";

try {
    // Test Diagnosis model
    $diagnosis = new Diagnosis();
    $methods = get_class_methods($diagnosis);

    echo "   - Diagnosis model methods: ";
    if (in_array('aiAssistantResults', $methods)) {
        echo "✓ aiAssistantResults relationship exists\n";
    } else {
        echo "✗ aiAssistantResults relationship missing\n";
    }

    // Test AiAssistantResult model
    $aiResult = new AiAssistantResult();
    $methods = get_class_methods($aiResult);

    echo "   - AiAssistantResult model methods: ";
    if (in_array('diagnosis', $methods)) {
        echo "✓ diagnosis relationship exists\n";
    } else {
        echo "✗ diagnosis relationship missing\n";
    }

} catch (Exception $e) {
    echo "   ✗ Error testing relationships: " . $e->getMessage() . "\n";
}

// Test 2: Check database structure
echo "\n2. Testing Database Structure:\n";

try {
    // Check if ai_assistant_results table exists
    $pdo = new PDO('sqlite:' . database_path('database.sqlite'));

    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('ai_assistant_results', $tables)) {
        echo "   ✓ ai_assistant_results table exists\n";

        // Check table structure
        $columns = $pdo->query("PRAGMA table_info(ai_assistant_results)")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');

        $requiredColumns = ['id', 'diagnosis_id', 'source', 'ai_analysis', 'created_at', 'updated_at'];
        $missingColumns = array_diff($requiredColumns, $columnNames);

        if (empty($missingColumns)) {
            echo "   ✓ All required columns exist\n";
        } else {
            echo "   ✗ Missing columns: " . implode(', ', $missingColumns) . "\n";
        }
    } else {
        echo "   ✗ ai_assistant_results table missing\n";
    }

    if (in_array('diagnoses', $tables)) {
        echo "   ✓ diagnoses table exists\n";
    } else {
        echo "   ✗ diagnoses table missing\n";
    }

} catch (Exception $e) {
    echo "   ✗ Error checking database: " . $e->getMessage() . "\n";
}

// Test 3: Check route structure
echo "\n3. Testing Routes:\n";

$routeFile = file_get_contents(__DIR__ . '/routes/web.php');

if (strpos($routeFile, 'openai.create-manual-diagnosis') !== false) {
    echo "   ✓ Manual diagnosis route exists\n";
} else {
    echo "   ✗ Manual diagnosis route missing\n";
}

if (strpos($routeFile, 'diagnosis.patient.index') !== false) {
    echo "   ✓ Patient diagnosis index route exists\n";
} else {
    echo "   ✗ Patient diagnosis index route missing\n";
}

// Test 4: Check view files
echo "\n4. Testing View Files:\n";

$viewFiles = [
    'resources/views/openai.blade.php' => 'AI diagnosis page',
    'resources/views/livewire/voice-assistant.blade.php' => 'Voice assistant page',
    'resources/views/diagnosis/patient-index.blade.php' => 'Patient diagnosis index',
    'resources/views/diagnosis/patient-view.blade.php' => 'Patient diagnosis view',
    'resources/views/cases.blade.php' => 'Cases page'
];

foreach ($viewFiles as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✓ $description exists\n";

        $content = file_get_contents(__DIR__ . '/' . $file);

        // Check for manual diagnosis form in AI page
        if ($file === 'resources/views/openai.blade.php') {
            if (strpos($content, 'manual-diagnosis-form') !== false) {
                echo "     ✓ Manual diagnosis form found\n";
            } else {
                echo "     ✗ Manual diagnosis form missing\n";
            }
        }

        // Check for AI assistant results in patient views
        if (strpos($file, 'patient-') !== false) {
            if (strpos($content, 'aiAssistantResults') !== false) {
                echo "     ✓ AI assistant results integration found\n";
            } else {
                echo "     ✗ AI assistant results integration missing\n";
            }
        }

    } else {
        echo "   ✗ $description missing\n";
    }
}

// Test 5: Check controller methods
echo "\n5. Testing Controller Methods:\n";

$controllers = [
    'app/Http/Controllers/OpenAIController.php' => ['createManualDiagnosis'],
    'app/Http/Controllers/DiagnosisController.php' => ['patientIndex', 'patientView'],
    'app/Livewire/VoiceAssistant.php' => ['createManualDiagnosis']
];

foreach ($controllers as $file => $methods) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $content = file_get_contents(__DIR__ . '/' . $file);

        foreach ($methods as $method) {
            if (strpos($content, "function $method") !== false || strpos($content, "public function $method") !== false) {
                echo "   ✓ $method method exists in " . basename($file) . "\n";
            } else {
                echo "   ✗ $method method missing in " . basename($file) . "\n";
            }
        }
    } else {
        echo "   ✗ " . basename($file) . " missing\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "Please review the results above to ensure all components are properly implemented.\n";
