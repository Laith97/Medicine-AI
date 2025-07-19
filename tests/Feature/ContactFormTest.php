<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function contact_form_can_be_rendered()
    {
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertSee('Get in Touch with Our Team');
    }

    /** @test */
    public function contact_form_requires_validation()
    {
        $response = $this->withoutMiddleware()
                        ->post('/contact', []);
        
        $response->assertSessionHasErrors([
            'name', 'email', 'subject', 'message'
        ]);
    }

    /** @test */
    public function contact_form_sends_email_successfully()
    {
        Mail::fake();
        
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123-456-7890',
            'service' => 'General Inquiry',
            'subject' => 'Test Subject',
            'message' => 'This is a test message'
        ];

        $response = $this->withoutMiddleware()
                        ->post('/contact', $contactData);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Just verify that an email was sent (simplified test)
        Mail::assertSentCount(1);
    }

    /** @test */
    public function contact_form_handles_ajax_requests()
    {
        Mail::fake();
        
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Test Subject',
            'message' => 'This is a test message'
        ];

        $response = $this->withoutMiddleware()
                        ->postJson('/contact', $contactData);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Thank you for your message! We will get back to you soon.'
        ]);
        
        // Just verify that an email was sent (simplified test)
        Mail::assertSentCount(1);
    }
}