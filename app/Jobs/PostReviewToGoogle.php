<?php

namespace App\Jobs;

use App\Models\Review;
use App\Services\GoogleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Exception;
use Illuminate\Support\Facades\Log;

class PostReviewToGoogle implements ShouldQueue
{
    use Queueable;

    protected $reviewId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct($reviewId)
    {
        $this->reviewId = $reviewId;
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleService $googleService): void
    {
        try {
            // Get the review
            $review = Review::find($this->reviewId);

            if (!$review) {
                Log::warning("Review not found: {$this->reviewId}");
                return;
            }

            // Check if the review has consent for Google posting
            if (!$review->consent_google_posting) {
                Log::info("Review does not have consent for Google posting: {$this->reviewId}");
                return;
            }

            // Check if the review is already posted to Google
            if ($review->posted_to_google) {
                Log::info("Review already posted to Google: {$this->reviewId}");
                return;
            }

            // Post the review to Google
            $googleService->postReview($review);

            Log::info("Review posted to Google successfully: {$this->reviewId}");
        } catch (Exception $e) {
            Log::error("Failed to post review to Google: {$this->reviewId}. Error: " . $e->getMessage());
            throw $e;
        }
    }
}
