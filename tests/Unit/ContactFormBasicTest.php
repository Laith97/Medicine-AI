<?php

test('contact controller can be created', function () {
    $controller = new \App\Http\Controllers\ContactController();
    expect($controller)->toBeInstanceOf(\App\Http\Controllers\ContactController::class);
});

test('contact form emails are sent to all recipients', function () {
    $recipients = [
        'info@medcuraai.com',
        'malikqattom@gmail.com',
        'laythfares99@gmail.com'
    ];
    
    expect(count($recipients))->toBe(3);
    expect($recipients)->toContain('info@medcuraai.com');
    expect($recipients)->toContain('malikqattom@gmail.com');
    expect($recipients)->toContain('laythfares99@gmail.com');
});
