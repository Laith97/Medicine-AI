<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoogleReviewResponse;
use App\Models\Review;
use App\Models\ReviewAiSetting;
use App\Services\AiReviewResponseGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\AiGenerationException;

class GoogleReviewController extends Controller
{
    public function __construct(
        private AiReviewResponseGenerator $reviewGenerator
    ) {}

    /**
     * Get AI settings for the authenticated doctor.
     */
    public function getSettings(): JsonResponse
    {
        $settings = ReviewAiSetting::firstOrCreate(
            ['doctor_id' => Auth::id()],
            [
                'auto_generate_enabled' => false,
                'auto_post_enabled' => false,
                'default_tone' => 'professional',
                'min_rating_for_auto_response' => 4,
                'respond_to_negative' => true,
            ]
        );

        return response()->json(['settings' => $settings]);
    }

    /**
     * Update AI settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'auto_generate_enabled' => 'sometimes|boolean',
            'auto_post_enabled' => 'sometimes|boolean',
            'default_tone' => 'sometimes|in:professional,friendly,empathetic,formal',
            'custom_instructions' => 'nullable|array',
            'min_rating_for_auto_response' => 'sometimes|integer|min:1|max:5',
            'respond_to_negative' => 'sometimes|boolean',
        ]);

        $settings = ReviewAiSetting::updateOrCreate(
            ['doctor_id' => Auth::id()],
            $validated
        );

        return response()->json([
            'settings' => $settings,
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * List pending AI-generated responses.
     */
    public function indexPendingResponses(): JsonResponse
    {
        $responses = GoogleReviewResponse::where('doctor_id', Auth::id())
            ->with('review')
            ->pending()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($responses);
    }

    /**
     * Generate AI response for a review.
     */
    public function generateResponse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'review_id' => 'required|exists:reviews,id',
            'tone' => 'nullable|in:professional,friendly,empathetic,formal',
        ]);

        $review = Review::findOrFail($validated['review_id']);

        if ($review->doctor_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get tone from settings if not provided
        $settings = ReviewAiSetting::firstOrCreate(['doctor_id' => Auth::id()]);
        $tone = $validated['tone'] ?? $settings->default_tone;

        // Use transaction to prevent race conditions
        try {
            return DB::transaction(function () use ($review, $tone) {
                // Check if response already exists (with lock to prevent race)
                $existingResponse = GoogleReviewResponse::where('review_id', $review->id)
                    ->lockForUpdate()
                    ->first();

                if ($existingResponse) {
                    return response()->json([
                        'response' => $existingResponse,
                        'message' => 'Response already exists',
                    ]);
                }

                // Generate response using AI
                try {
                    $generatedText = $this->generateAiResponse($review, $tone);
                } catch (AiGenerationException $e) {
                    Log::error('AI review response generation failed', [
                        'review_id' => $review->id,
                        'error' => $e->getMessage(),
                    ]);
                    return response()->json([
                        'error' => 'AI response generation failed',
                        'message' => $e->getMessage(),
                    ], 500);
                }

                $response = GoogleReviewResponse::create([
                    'review_id' => $review->id,
                    'doctor_id' => Auth::id(),
                    'generated_response' => $generatedText,
                    'tone' => $tone,
                    'status' => 'draft',
                ]);

                return response()->json([
                    'response' => $response->load('review'),
                    'message' => 'AI response generated successfully',
                ], 201);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation (race condition)
            if ($e->getCode() === '23000') {
                $existingResponse = GoogleReviewResponse::where('review_id', $review->id)->first();
                return response()->json([
                    'response' => $existingResponse,
                    'message' => 'Response already exists',
                ]);
            }
            throw $e;
        }
    }

    /**
     * Approve and optionally modify a response.
     */
    public function approveResponse(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'approved_response' => 'nullable|string',
        ]);

        $response = GoogleReviewResponse::where('doctor_id', Auth::id())
            ->findOrFail($id);

        $response->approve(Auth::user(), $validated['approved_response'] ?? null);

        return response()->json([
            'response' => $response->fresh()->load('review'),
            'message' => 'Response approved',
        ]);
    }

    /**
     * Reject a response.
     */
    public function rejectResponse(int $id): JsonResponse
    {
        $response = GoogleReviewResponse::where('doctor_id', Auth::id())
            ->findOrFail($id);

        $response->reject();

        return response()->json([
            'message' => 'Response rejected',
        ]);
    }

    /**
     * Mark response as posted to Google.
     */
    public function markAsPosted(int $id): JsonResponse
    {
        $response = GoogleReviewResponse::where('doctor_id', Auth::id())
            ->where('status', 'approved')
            ->findOrFail($id);

        $response->markAsPosted();

        // Update the review's posted_to_google flag
        $response->review->markAsPostedToGoogle();

        return response()->json([
            'response' => $response->fresh(),
            'message' => 'Response marked as posted',
        ]);
    }

    /**
     * Generate response using AI.
     *
     * @throws AiGenerationException
     */
    private function generateAiResponse(Review $review, string $tone): string
    {
        return $this->reviewGenerator->generateResponse($review, $tone);
    }
}
