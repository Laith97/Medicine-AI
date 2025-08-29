<?php

require_once 'vendor/autoload.php';

use Pusher\Pusher;

try {
    $pusher = new Pusher(
        'dd2dc532e8700af37cf8',  // key
        '873511f2f4a8f0e37f17',  // secret
        '2033834',               // app_id
        [
            'cluster' => 'ap2',
            'useTLS' => true
        ]
    );

    echo "Testing Pusher connection...\n";

    // Test 1: Get app info
    echo "Test 1: Getting app info...\n";
    $response = $pusher->get('/apps/2033834');
    echo "Response: " . json_encode($response) . "\n\n";

    // Test 2: Try to trigger an event
    echo "Test 2: Triggering test event...\n";
    $result = $pusher->trigger('test-channel', 'test-event', ['message' => 'Hello World']);
    echo "Trigger result: " . json_encode($result) . "\n\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
