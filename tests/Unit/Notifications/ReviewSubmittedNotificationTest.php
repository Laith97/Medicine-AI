<?php

namespace Tests\Unit\Notifications;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Review;
use App\Notifications\ReviewSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReviewSubmittedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $patient;
    protected $review;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->create(['role' => 'doctor']);
        $this->patient = User::factory()->create(['role' => 'patient']);

        $this->review = Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'rating' => 5,
            'comment' => 'Excellent service, very professional and caring.',
            'is_approved' => true,
            'is_anonymous' => false,
        ]);
    }

    /** @test */
    public function it_can_be_created()
    {
        $notification = new ReviewSubmittedNotification($this->review);

        $this->assertInstanceOf(ReviewSubmittedNotification::class, $notification);
    }

    /** @test */
    public function it_has_correct_notification_channels()
    {
        $notification = new ReviewSubmittedNotification($this->review);

        $this->assertEquals(['database', 'mail'], $notification->via($this->doctor));
    }

    /** @test */
    public function it_has_correct_array_content_for_doctor()
    {
        $notification = new ReviewSubmittedNotification($this->review);

        $arrayContent = $notification->toArray($this->doctor);

        $this->assertEquals('review_submitted', $arrayContent['type']);
        $this->assertEquals('New Review Submitted', $arrayContent['title']);
        $this->assertStringContainsString("A new review has been submitted by {$this->review->patient->name} with a rating of {$this->review->rating} stars", $arrayContent['message']);
        $this->assertEquals('star', $arrayContent['icon']);
        $this->assertEquals(route('doctor.reviews.index'), $arrayContent['link']);
        $this->assertEquals('View Reviews', $arrayContent['link_text']);
        $this->assertEquals('review', $arrayContent['related_type']);
        $this->assertEquals($this->review->id, $arrayContent['related_id']);

        $this->assertArrayHasKey('data', $arrayContent);
        $this->assertEquals($this->review->id, $arrayContent['data']['review_id']);
        $this->assertEquals($this->review->patient->name, $arrayContent['data']['patient_name']);
        $this->assertEquals($this->review->rating, $arrayContent['data']['rating']);
        $this->assertEquals($this->review->comment, $arrayContent['data']['comment']);
        $this->assertFalse($arrayContent['data']['is_anonymous']);
        $this->assertEquals($this->review->created_at->format('Y-m-d H:i:s'), $arrayContent['data']['submitted_at']);
    }

    /** @test */
    public function it_has_correct_mail_content_for_doctor()
    {
        $notification = new ReviewSubmittedNotification($this->review);

        $mailData = $notification->toMail($this->doctor);

        $this->assertEquals('New Review Submitted', $mailData->subject);
        $this->assertStringContainsString("Hello {$this->doctor->name}", $mailData->greeting);
        $this->assertStringContainsString("A new review has been submitted for {$this->review->doctor->name}", $mailData->introLines[0]);
        $this->assertStringContainsString('Rating: ' . $this->review->rating . ' stars', $mailData->introLines[1]);
        $this->assertStringContainsString('Comment: ' . $this->review->comment, $mailData->introLines[2]);
        $this->assertStringContainsString('View Review', $mailData->actionText);
        $this->assertEquals(route('reviews.show', $this->review), $mailData->actionUrl);
        $this->assertStringContainsString('Thank you for using our platform', $mailData->outroLines[0]);
    }

    /** @test */
    public function it_has_correct_sms_content()
    {
        $notification = new ReviewSubmittedNotification($this->review);

        $smsContent = $notification->toSms($this->doctor);

        $this->assertStringContainsString("New review submitted for {$this->review->doctor->name}", $smsContent);
        $this->assertStringContainsString("Rating: {$this->review->rating} stars", $smsContent);
        $this->assertStringContainsString(route('reviews.show', $this->review), $smsContent);
    }

    /** @test */
    public function it_can_be_sent_to_doctor()
    {
        $notification = new ReviewSubmittedNotification($this->review);

        $this->doctor->notify($notification);

        $this->assertEquals(1, $this->doctor->notifications()->whereNull('read_at')->count());

        $storedNotification = $this->doctor->notifications()->first();
        $data = json_decode($storedNotification->data, true);

        $this->assertEquals('New Review Submitted', $data['title']);
        $this->assertEquals('review_submitted', $storedNotification->type);
    }

    /** @test */
    public function it_handles_anonymous_reviews()
    {
        $review = Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'rating' => 4,
            'comment' => 'Good service, would recommend.',
            'is_approved' => true,
            'is_anonymous' => true,
        ]);

        $notification = new ReviewSubmittedNotification($review);

        $arrayContent = $notification->toArray($this->doctor);

        $this->assertEquals('Anonymous Patient', $arrayContent['data']['patient_name']);
        $this->assertTrue($arrayContent['data']['is_anonymous']);
        $this->assertStringContainsString('A new review has been submitted by Anonymous Patient with a rating of 4 stars', $arrayContent['message']);
    }

    /** @test */
    public function it_handles_guest_reviews()
    {
        $review = Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => null,
            'guest_name' => 'John Doe',
            'guest_email' => 'john@example.com',
            'rating' => 5,
            'comment' => 'Excellent service!',
            'is_approved' => true,
            'is_anonymous' => false,
        ]);

        $notification = new ReviewSubmittedNotification($review);

        $arrayContent = $notification->toArray($this->doctor);

        $this->assertEquals('John Doe', $arrayContent['data']['patient_name']);
        $this->assertFalse($arrayContent['data']['is_anonymous']);
        $this->assertStringContainsString('A new review has been submitted by John Doe with a rating of 5 stars', $arrayContent['message']);
    }

    /** @test */
    public function it_handles_different_ratings()
    {
        $ratings = [1, 2, 3, 4, 5];

        foreach ($ratings as $rating) {
            $review = Review::factory()->create([
                'doctor_id' => $this->doctor->id,
                'patient_id' => $this->patient->id,
                'rating' => $rating,
                'comment' => 'Test comment for rating ' . $rating,
                'is_approved' => true,
                'is_anonymous' => false,
            ]);

            $notification = new ReviewSubmittedNotification($review);

            $arrayContent = $notification->toArray($this->doctor);

            $this->assertEquals('review_submitted', $arrayContent['type']);
            $this->assertEquals('New Review Submitted', $arrayContent['title']);
            $this->assertStringContainsString("with a rating of {$rating} stars", $arrayContent['message']);
            $this->assertEquals($rating, $arrayContent['data']['rating']);
        }
    }

    /** @test */
    public function it_handles_long_comments()
    {
        $longComment = str_repeat('This is a very long comment that should be properly handled by the notification system. ', 10);

        $review = Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'rating' => 5,
            'comment' => $longComment,
            'is_approved' => true,
            'is_anonymous' => false,
        ]);

        $notification = new ReviewSubmittedNotification($review);

        $arrayContent = $notification->toArray($this->doctor);

        $this->assertEquals('review_submitted', $arrayContent['type']);
        $this->assertEquals('New Review Submitted', $arrayContent['title']);
        $this->assertEquals($longComment, $arrayContent['data']['comment']);
    }

    /** @test */
    public function it_handles_pending_reviews()
    {
        $review = Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'rating' => 5,
            'comment' => 'Test comment for pending review',
            'is_approved' => false,
            'is_anonymous' => false,
        ]);

        $notification = new ReviewSubmittedNotification($review);

        $arrayContent = $notification->toArray($this->doctor);

        $this->assertEquals('review_submitted', $arrayContent['type']);
        $this->assertEquals('New Review Submitted', $arrayContent['title']);
        $this->assertEquals(5, $arrayContent['data']['rating']);
        $this->assertEquals('Test comment for pending review', $arrayContent['data']['comment']);
    }

    /** @test */
    public function it_handles_google_reviews()
    {
        $review = Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'rating' => 5,
            'comment' => 'Great service!',
            'is_approved' => true,
            'is_anonymous' => false,
            'source' => 'google',
            'posted_to_google' => true,
            'google_review_id' => 'google-review-123',
            'google_posted_at' => now(),
        ]);

        $notification = new ReviewSubmittedNotification($review);

        $arrayContent = $notification->toArray($this->doctor);

        $this->assertEquals('review_submitted', $arrayContent['type']);
        $this->assertEquals('New Review Submitted', $arrayContent['title']);
        $this->assertEquals(5, $arrayContent['data']['rating']);
        $this->assertEquals('Great service!', $arrayContent['data']['comment']);
    }

    /** @test */
    public function it_handles_reviews_with_appointment()
    {
        $appointment = \App\Models\Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'completed',
        ]);

        $review = Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $appointment->id,
            'rating' => 5,
            'comment' => 'Great appointment!',
            'is_approved' => true,
            'is_anonymous' => false,
        ]);

        $notification = new ReviewSubmittedNotification($review);

        $arrayContent = $notification->toArray($this->doctor);

        $this->assertEquals('review_submitted', $arrayContent['type']);
        $this->assertEquals('New Review Submitted', $arrayContent['title']);
        $this->assertEquals(5, $arrayContent['data']['rating']);
        $this->assertEquals('Great appointment!', $arrayContent['data']['comment']);
    }
}
