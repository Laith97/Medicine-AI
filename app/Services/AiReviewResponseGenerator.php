<?php

namespace App\Services;

use App\Models\GoogleReviewResponse;
use App\Models\Review;
use App\Models\ReviewAiSetting;
use App\Exceptions\AiGenerationException;
use Illuminate\Support\Facades\Log;

class AiReviewResponseGenerator
{
    public function __construct(
        private AIAssistant $aiAssistant
    ) {}

    /**
     * Generate AI response for a review.
     *
     * @throws AiGenerationException
     */
    public function generateResponse(Review $review, ?string $tone = null): string
    {
        $settings = ReviewAiSetting::firstOrCreate(['doctor_id' => $review->doctor_id]);
        $tone = $tone ?? $settings->default_tone;

        $prompt = $this->buildPrompt($review, $tone, $settings);

        $result = $this->aiAssistant->write($prompt);

        if (isset($result['error'])) {
            throw new AiGenerationException(
                'AI response generation failed: ' . $result['error'],
                0,
                null
            );
        }

        if (empty($result['content'])) {
            throw new AiGenerationException(
                'AI response generation returned empty content',
                0,
                null
            );
        }

        return $result['content'];
    }

    /**
     * Build prompt for AI.
     */
    private function buildPrompt(Review $review, string $tone, ReviewAiSetting $settings): string
    {
        $toneInstructions = $this->getToneInstructions($tone);
        $customInstructions = $settings->custom_instructions ?? [];

        $prompt = "You are a professional healthcare provider's administrative assistant. ";
        $prompt .= "Generate a response to a patient review.\n\n";
        $prompt .= $toneInstructions . "\n\n";
        $prompt .= "Review Details:\n";
        $prompt .= "- Rating: {$review->rating} star(s)\n";
        $prompt .= "- Content: {$review->content}\n";
        $reviewerName = $review->reviewer_name ?? 'Anonymous';
        $prompt .= "- Patient Name: {$reviewerName}\n\n";

        if (!empty($customInstructions)) {
            $prompt .= "Provider's Custom Instructions:\n";
            foreach ($customInstructions as $key => $instruction) {
                $prompt .= "- {$key}: {$instruction}\n";
            }
            $prompt .= "\n";
        }

        // Response guidelines
        $prompt .= "Guidelines:\n";
        $prompt .= "- Keep response to 2-4 sentences\n";
        $prompt .= "- Do not make medical claims\n";
        $prompt .= "- Do not apologize excessively\n";
        $prompt .= "- If negative review (1-2 stars): Acknowledge concerns, express empathy, invite to contact office\n";
        $prompt .= "- If neutral review (3 stars): Thank for feedback, mention commitment to improvement\n";
        $prompt .= "- If positive review (4-5 stars): Express gratitude, reinforce positive aspects\n";
        $prompt .= "- Never include personal health advice\n";
        $prompt .= "- Maintain HIPAA compliance (do not acknowledge specific treatments)\n";

        return $prompt;
    }

    /**
     * Get tone-specific instructions.
     */
    private function getToneInstructions(string $tone): string
    {
        return match ($tone) {
            'professional' => 'Use a professional, courteous, and concise tone. Avoid overly casual language.',
            'friendly' => 'Use a warm, friendly, and approachable tone. Feel free to use lighter language.',
            'empathetic' => 'Use an empathetic and understanding tone. Show genuine care for the patient experience.',
            'formal' => 'Use a formal and respectful tone. Maintain professional distance while being courteous.',
            default => 'Use a professional and courteous tone.',
        };
    }

    /**
     * Batch generate responses for multiple reviews.
     * Continues processing even if some fail, returns both successes and failures.
     */
    public function batchGenerate(array $reviewIds, ?string $tone = null): array
    {
        $results = [];

        foreach ($reviewIds as $reviewId) {
            try {
                $result = $this->processReviewForBatch($reviewId, $tone);
                $results[$reviewId] = $result;
            } catch (\Exception $e) {
                Log::error('Batch response generation failed for review', [
                    'review_id' => $reviewId,
                    'error' => $e->getMessage(),
                ]);
                $results[$reviewId] = [
                    'error' => 'Generation failed: ' . $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Process a single review for batch generation.
     *
     * @throws AiGenerationException
     */
    private function processReviewForBatch(int $reviewId, ?string $tone): array
    {
        $review = Review::find($reviewId);

        if (!$review) {
            return ['error' => 'Review not found'];
        }

        $settings = ReviewAiSetting::firstOrCreate(['doctor_id' => $review->doctor_id]);

        // Check if should auto-generate based on settings
        if (!$settings->isAutoGenerateEnabled()) {
            return ['skipped' => 'Auto-generate disabled'];
        }

        // Check minimum rating threshold
        if (!$settings->shouldAutoRespond($review->rating)) {
            return ['skipped' => 'Below minimum rating threshold'];
        }

        // Check if negative review and respond_to_negative is false
        if ($review->rating <= 2 && !$settings->respond_to_negative) {
            return ['skipped' => 'Negative review auto-response disabled'];
        }

        // Check if response already exists
        $existing = GoogleReviewResponse::where('review_id', $review->id)->first();
        if ($existing) {
            return ['skipped' => 'Response already exists'];
        }

        // Generate response - this may throw AiGenerationException
        $responseText = $this->generateResponse($review, $tone);

        $response = GoogleReviewResponse::create([
            'review_id' => $review->id,
            'doctor_id' => $review->doctor_id,
            'generated_response' => $responseText,
            'tone' => $tone ?? $settings->default_tone,
            'status' => 'draft',
        ]);

        return [
            'success' => true,
            'response_id' => $response->id,
        ];
    }
}
