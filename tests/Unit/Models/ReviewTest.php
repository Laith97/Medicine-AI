<?php

namespace Tests\Unit\Models;

use App\Models\Review;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected $review;
    protected $patient;
    protected $doctor;
    protected $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->create(['role' => 'patient']);
        $this->doctorUser = User::factory()->create(['role' => 'doctor']);

        $specialty = Specialty::factory()->create();
        $this->doctor = Doctor::factory()->create([
            'user_id' => $this->doctorUser->id,
            'specialty_id' => $specialty->id
        ]);

        $this->review = Review::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'rating' => 5,
            'comment' => 'Excellent service and care!',
            'is_approved' => true,
            'is_public' => true,
            'consent_google_posting' => true,
            'posted_to_google' => false,
            'source' => 'medcura'
        ]);
    }

    public function test_review_can_be_created()
    {
        $this->assertInstanceOf(Review::class, $this->review);
        $this->assertEquals($this->patient->id, $this->review->patient_id);
        $this->assertEquals($this->doctor->id, $this->review->doctor_id);
        $this->assertEquals(5, $this->review->rating);
    }

    public function test_review_has_fillable_attributes()
    {
        $fillable = [
            'patient_id', 'doctor_id', 'appointment_id', 'rating', 'comment',
            'is_approved', 'is_public', 'approved_at', 'approved_by',
            'consent_google_posting', 'posted_to_google', 'google_review_id',
            'posted_to_google_at', 'source', 'helpful_count', 'reported_count',
            'moderation_notes'
        ];

        $this->assertEquals($fillable, $this->review->getFillable());
    }

    public function test_review_casts_attributes_correctly()
    {
        $this->assertIsInt($this->review->rating);
        $this->assertIsBool($this->review->is_approved);
        $this->assertIsBool($this->review->is_public);
        $this->assertIsBool($this->review->consent_google_posting);
        $this->assertIsBool($this->review->posted_to_google);
        $this->assertIsInt($this->review->helpful_count);
        $this->assertIsInt($this->review->reported_count);
    }

    public function test_review_patient_relationship()
    {
        $this->assertInstanceOf(User::class, $this->review->patient);
        $this->assertEquals($this->patient->id, $this->review->patient->id);
    }

    public function test_review_doctor_relationship()
    {
        $this->assertInstanceOf(Doctor::class, $this->review->doctor);
        $this->assertEquals($this->doctor->id, $this->review->doctor->id);
    }

    public function test_review_approved_scope()
    {
        $approvedReview = Review::factory()->create(['is_approved' => true]);
        $unapprovedReview = Review::factory()->create(['is_approved' => false]);

        $approvedReviews = Review::approved()->get();

        $this->assertTrue($approvedReviews->contains($approvedReview));
        $this->assertFalse($approvedReviews->contains($unapprovedReview));
    }

    public function test_review_pending_scope()
    {
        $pendingReview = Review::factory()->create(['is_approved' => false]);
        $approvedReview = Review::factory()->create(['is_approved' => true]);

        $pendingReviews = Review::pending()->get();

        $this->assertTrue($pendingReviews->contains($pendingReview));
        $this->assertFalse($pendingReviews->contains($approvedReview));
    }

    public function test_review_public_scope()
    {
        $publicReview = Review::factory()->create(['is_public' => true]);
        $privateReview = Review::factory()->create(['is_public' => false]);

        $publicReviews = Review::public()->get();

        $this->assertTrue($publicReviews->contains($publicReview));
        $this->assertFalse($publicReviews->contains($privateReview));
    }

    public function test_review_by_rating_scope()
    {
        $fiveStarReview = Review::factory()->create(['rating' => 5]);
        $fourStarReview = Review::factory()->create(['rating' => 4]);
        $threeStarReview = Review::factory()->create(['rating' => 3]);

        $fiveStarReviews = Review::byRating(5)->get();
        $fourPlusReviews = Review::byRating(4, '>=')->get();

        $this->assertTrue($fiveStarReviews->contains($fiveStarReview));
        $this->assertFalse($fiveStarReviews->contains($fourStarReview));

        $this->assertTrue($fourPlusReviews->contains($fiveStarReview));
        $this->assertTrue($fourPlusReviews->contains($fourStarReview));
        $this->assertFalse($fourPlusReviews->contains($threeStarReview));
    }

    public function test_review_recent_scope()
    {
        $recentReview = Review::factory()->create(['created_at' => now()->subDays(5)]);
        $oldReview = Review::factory()->create(['created_at' => now()->subDays(35)]);

        $recentReviews = Review::recent(30)->get(); // Last 30 days

        $this->assertTrue($recentReviews->contains($recentReview));
        $this->assertFalse($recentReviews->contains($oldReview));
    }

    public function test_review_with_consent_scope()
    {
        $consentReview = Review::factory()->create(['consent_google_posting' => true]);
        $noConsentReview = Review::factory()->create(['consent_google_posting' => false]);

        $consentReviews = Review::withConsent()->get();

        $this->assertTrue($consentReviews->contains($consentReview));
        $this->assertFalse($consentReviews->contains($noConsentReview));
    }

    public function test_review_not_posted_to_google_scope()
    {
        $notPostedReview = Review::factory()->create(['posted_to_google' => false]);
        $postedReview = Review::factory()->create(['posted_to_google' => true]);

        $notPostedReviews = Review::notPostedToGoogle()->get();

        $this->assertTrue($notPostedReviews->contains($notPostedReview));
        $this->assertFalse($notPostedReviews->contains($postedReview));
    }

    public function test_review_get_rating_stars_attribute()
    {
        $this->review->rating = 5;
        $this->assertEquals('★★★★★', $this->review->rating_stars);

        $this->review->rating = 3;
        $this->assertEquals('★★★☆☆', $this->review->rating_stars);

        $this->review->rating = 1;
        $this->assertEquals('★☆☆☆☆', $this->review->rating_stars);
    }

    public function test_review_get_rating_color_attribute()
    {
        $this->review->rating = 5;
        $this->assertEquals('success', $this->review->rating_color);

        $this->review->rating = 4;
        $this->assertEquals('success', $this->review->rating_color);

        $this->review->rating = 3;
        $this->assertEquals('warning', $this->review->rating_color);

        $this->review->rating = 2;
        $this->assertEquals('danger', $this->review->rating_color);

        $this->review->rating = 1;
        $this->assertEquals('danger', $this->review->rating_color);
    }

    public function test_review_get_truncated_comment_attribute()
    {
        $longComment = str_repeat('This is a very long comment. ', 20);
        $this->review->comment = $longComment;

        $truncated = $this->review->truncated_comment;
        $this->assertTrue(strlen($truncated) <= 150);
        $this->assertStringEndsWith('...', $truncated);

        $shortComment = 'Short comment';
        $this->review->comment = $shortComment;
        $this->assertEquals($shortComment, $this->review->truncated_comment);
    }

    public function test_review_is_approved_method()
    {
        $this->assertTrue($this->review->isApproved());

        $this->review->is_approved = false;
        $this->assertFalse($this->review->isApproved());
    }

    public function test_review_is_pending_method()
    {
        $this->assertFalse($this->review->isPending());

        $this->review->is_approved = false;
        $this->assertTrue($this->review->isPending());
    }

    public function test_review_is_public_method()
    {
        $this->assertTrue($this->review->isPublic());

        $this->review->is_public = false;
        $this->assertFalse($this->review->isPublic());
    }

    public function test_review_has_consent_for_google_method()
    {
        $this->assertTrue($this->review->hasConsentForGoogle());

        $this->review->consent_google_posting = false;
        $this->assertFalse($this->review->hasConsentForGoogle());
    }

    public function test_review_is_posted_to_google_method()
    {
        $this->assertFalse($this->review->isPostedToGoogle());

        $this->review->posted_to_google = true;
        $this->assertTrue($this->review->isPostedToGoogle());
    }

    public function test_review_can_be_posted_to_google_method()
    {
        // Should be true when approved, has consent, and not yet posted
        $this->assertTrue($this->review->canBePostedToGoogle());

        // Should be false when not approved
        $this->review->is_approved = false;
        $this->assertFalse($this->review->canBePostedToGoogle());

        // Should be false when no consent
        $this->review->is_approved = true;
        $this->review->consent_google_posting = false;
        $this->assertFalse($this->review->canBePostedToGoogle());

        // Should be false when already posted
        $this->review->consent_google_posting = true;
        $this->review->posted_to_google = true;
        $this->assertFalse($this->review->canBePostedToGoogle());
    }

    public function test_review_approve_method()
    {
        $approver = User::factory()->create();
        $unapprovedReview = Review::factory()->create(['is_approved' => false]);

        $unapprovedReview->approve($approver->id);

        $this->assertTrue($unapprovedReview->is_approved);
        $this->assertEquals($approver->id, $unapprovedReview->approved_by);
        $this->assertNotNull($unapprovedReview->approved_at);
    }

    public function test_review_reject_method()
    {
        $this->review->reject('Inappropriate content');

        $this->assertFalse($this->review->is_approved);
        $this->assertEquals('Inappropriate content', $this->review->moderation_notes);
    }

    public function test_review_mark_as_posted_to_google_method()
    {
        $googleReviewId = 'google_review_123';

        $this->review->markAsPostedToGoogle($googleReviewId);

        $this->assertTrue($this->review->posted_to_google);
        $this->assertEquals($googleReviewId, $this->review->google_review_id);
        $this->assertNotNull($this->review->posted_to_google_at);
    }

    public function test_review_increment_helpful_count_method()
    {
        $initialCount = $this->review->helpful_count;

        $this->review->incrementHelpfulCount();

        $this->assertEquals($initialCount + 1, $this->review->helpful_count);
    }

    public function test_review_increment_reported_count_method()
    {
        $initialCount = $this->review->reported_count;

        $this->review->incrementReportedCount();

        $this->assertEquals($initialCount + 1, $this->review->reported_count);
    }

    public function test_review_get_sentiment_method()
    {
        // Test positive sentiment
        $this->review->rating = 5;
        $this->review->comment = 'Amazing doctor! Highly recommend!';
        $this->assertEquals('positive', $this->review->getSentiment());

        // Test negative sentiment
        $this->review->rating = 2;
        $this->review->comment = 'Poor service, very disappointed.';
        $this->assertEquals('negative', $this->review->getSentiment());

        // Test neutral sentiment
        $this->review->rating = 3;
        $this->review->comment = 'It was okay, nothing special.';
        $this->assertEquals('neutral', $this->review->getSentiment());
    }

    public function test_review_is_high_rating_method()
    {
        $this->review->rating = 5;
        $this->assertTrue($this->review->isHighRating());

        $this->review->rating = 4;
        $this->assertTrue($this->review->isHighRating());

        $this->review->rating = 3;
        $this->assertFalse($this->review->isHighRating());
    }

    public function test_review_is_low_rating_method()
    {
        $this->review->rating = 1;
        $this->assertTrue($this->review->isLowRating());

        $this->review->rating = 2;
        $this->assertTrue($this->review->isLowRating());

        $this->review->rating = 3;
        $this->assertFalse($this->review->isLowRating());
    }

    public function test_review_get_age_in_days_method()
    {
        $this->review->created_at = now()->subDays(5);
        $this->assertEquals(5, $this->review->getAgeInDays());

        $this->review->created_at = now()->subHours(12);
        $this->assertEquals(0, $this->review->getAgeInDays());
    }

    public function test_review_needs_moderation_method()
    {
        // High reported count should need moderation
        $this->review->reported_count = 5;
        $this->assertTrue($this->review->needsModeration());

        // Low rating with negative keywords should need moderation
        $this->review->reported_count = 0;
        $this->review->rating = 1;
        $this->review->comment = 'This doctor is terrible and unprofessional';
        $this->assertTrue($this->review->needsModeration());

        // Normal review should not need moderation
        $this->review->rating = 5;
        $this->review->comment = 'Great experience!';
        $this->assertFalse($this->review->needsModeration());
    }
}
