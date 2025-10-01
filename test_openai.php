<?php

/**
 * Test script for OpenAI JSON response parsing improvements
 * This script tests the new JSON parsing logic directly
 */

// Test the validateAndParseJsonResponse logic
function validateAndParseJsonResponse($aiContent, $maxRetries = 2) {
    $attempts = 0;
    $lastError = null;

    while ($attempts <= $maxRetries) {
        try {
            // Clean the content - remove any markdown formatting or extra text
            $cleanContent = trim($aiContent);

            // Remove markdown code blocks if present
            if (strpos($cleanContent, '```json') === 0) {
                $cleanContent = substr($cleanContent, 7);
            }
            if (strpos($cleanContent, '```') === 0) {
                $cleanContent = substr($cleanContent, 3);
            }
            if (str_ends_with($cleanContent, '```')) {
                $cleanContent = substr($cleanContent, 0, -3);
            }

            $cleanContent = trim($cleanContent);

            // Try to parse JSON
            $parsed = json_decode($cleanContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON decode error: ' . json_last_error_msg());
            }

            // Validate that we have the expected structure
            if (!is_array($parsed) || !array_key_exists('suggestions', $parsed) || !array_key_exists('risk_flags', $parsed)) {
                throw new Exception('Response missing required keys: suggestions and risk_flags');
            }

            return $parsed;

        } catch (Exception $e) {
            $lastError = $e;
            $attempts++;

            // If this isn't the last attempt, try to extract JSON from the content
            if ($attempts <= $maxRetries) {
                // Try to find JSON-like content within the response
                if (preg_match('/\{.*\}/s', $aiContent, $matches)) {
                    $aiContent = $matches[0];
                    continue;
                }
            }
        }
    }

    // All attempts failed
    throw new Exception('Failed to parse OpenAI response as valid JSON after ' . ($maxRetries + 1) . ' attempts: ' . ($lastError ? $lastError->getMessage() : 'Unknown error'));
}

// Test the validateResponseStructure logic
function validateResponseStructure($suggestions, $riskFlags) {
    $issues = [];
    $fallbackSuggestions = [];
    $fallbackRiskFlags = [];

    // Validate suggestions structure
    if (!is_array($suggestions)) {
        $issues[] = 'suggestions is not an array';
        $fallbackSuggestions = ['No specific medication suggestions available. Please consult medical guidelines.'];
    } else {
        // Validate each suggestion has required fields
        $validSuggestions = [];
        foreach ($suggestions as $suggestion) {
            if (is_array($suggestion) && isset($suggestion['med'])) {
                $validSuggestions[] = $suggestion;
            }
        }

        if (empty($validSuggestions)) {
            $issues[] = 'no valid suggestion objects found';
            $fallbackSuggestions = ['No specific medication suggestions available. Please consult medical guidelines.'];
        } else {
            $fallbackSuggestions = $validSuggestions;
        }
    }

    // Validate risk_flags structure
    if (!is_array($riskFlags)) {
        $issues[] = 'risk_flags is not an array';
        $fallbackRiskFlags = ['Please review patient history for additional risk factors.'];
    } else {
        $validRiskFlags = array_filter($riskFlags, 'is_string');
        if (empty($validRiskFlags)) {
            $issues[] = 'no valid risk flag strings found';
            $fallbackRiskFlags = ['Please review patient history for additional risk factors.'];
        } else {
            $fallbackRiskFlags = array_values($validRiskFlags);
        }
    }

    return [
        'valid' => empty($issues),
        'issues' => $issues,
        'fallback_suggestions' => $fallbackSuggestions,
        'fallback_risk_flags' => $fallbackRiskFlags,
    ];
}

echo "Testing OpenAI JSON Response Parsing Improvements\n";
echo "================================================\n\n";

// Test cases
$testCases = [
    // Valid JSON response
    [
        'name' => 'Valid JSON Response',
        'content' => '{"suggestions": [{"med": "Ibuprofen", "dosage": "400mg", "freq": "every 6-8 hours", "dur": "7 days", "confidence": 85, "reason": "NSAID for pain relief"}], "risk_flags": ["Monitor for GI upset", "Caution with kidney disease"]}',
        'expected' => 'success'
    ],

    // JSON wrapped in markdown
    [
        'name' => 'JSON in Markdown',
        'content' => '```json
{"suggestions": [{"med": "Acetaminophen", "dosage": "500mg", "freq": "every 4-6 hours", "dur": "5 days", "confidence": 90, "reason": "Antipyretic medication"}], "risk_flags": ["Liver toxicity risk"]}
```',
        'expected' => 'success'
    ],

    // Invalid JSON - missing required keys
    [
        'name' => 'Missing Required Keys',
        'content' => '{"medications": [{"name": "Aspirin"}], "warnings": ["Bleeding risk"]}',
        'expected' => 'error'
    ],

    // Malformed JSON
    [
        'name' => 'Malformed JSON',
        'content' => '{"suggestions": [{"med": "Ibuprofen", "dosage": "400mg"}, "risk_flags": ["GI upset"]',
        'expected' => 'error'
    ],

    // Text response instead of JSON
    [
        'name' => 'Text Response',
        'content' => 'Based on the patient symptoms, I recommend ibuprofen 400mg every 6-8 hours for pain relief. Please monitor for gastrointestinal side effects.',
        'expected' => 'error'
    ],

    // Valid JSON with invalid structure
    [
        'name' => 'Invalid Structure',
        'content' => '{"suggestions": "not an array", "risk_flags": ["valid flag"]}',
        'expected' => 'fallback'
    ]
];

$passed = 0;
$total = count($testCases);

foreach ($testCases as $i => $testCase) {
    echo "Test " . ($i + 1) . ": {$testCase['name']}\n";
    echo "Expected: {$testCase['expected']}\n";

    try {
        $result = validateAndParseJsonResponse($testCase['content']);

        if ($testCase['expected'] === 'success') {
            // Validate structure
            $validation = validateResponseStructure(
                $result['suggestions'] ?? [],
                $result['risk_flags'] ?? []
            );

            if ($validation['valid']) {
                echo "✅ PASSED - Valid JSON parsed successfully\n";
                $passed++;
            } else {
                echo "❌ FAILED - Structure validation failed: " . implode(', ', $validation['issues']) . "\n";
            }
        } elseif ($testCase['expected'] === 'fallback') {
            $validation = validateResponseStructure(
                $result['suggestions'] ?? [],
                $result['risk_flags'] ?? []
            );

            if (!$validation['valid']) {
                echo "✅ PASSED - Invalid structure detected and fallback provided\n";
                $passed++;
            } else {
                echo "❌ FAILED - Expected fallback but structure was valid\n";
            }
        } else {
            echo "❌ FAILED - Expected error but parsing succeeded\n";
        }

    } catch (Exception $e) {
        if (in_array($testCase['expected'], ['error', 'fallback'])) {
            echo "✅ PASSED - Correctly threw exception: " . $e->getMessage() . "\n";
            $passed++;
        } else {
            echo "❌ FAILED - Unexpected error: " . $e->getMessage() . "\n";
        }
    }

    echo "\n";
}

echo "================================================\n";
echo "Test Results: $passed/$total tests passed\n";

if ($passed === $total) {
    echo "🎉 All tests passed! OpenAI JSON parsing improvements are working correctly.\n";
} else {
    echo "⚠️  Some tests failed. Please review the implementation.\n";
}