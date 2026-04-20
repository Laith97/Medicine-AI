<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\HealthInsight;
use App\Services\HealthInsightsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HealthInsightsController extends Controller
{
    public function __construct(
        private HealthInsightsService $insightsService
    ) {}

    public function index(): View
    {
        $patient = Auth::user();

        $latestInsight = HealthInsight::getLatestForUser($patient->id);
        $pastInsights = HealthInsight::where('user_id', $patient->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('patient.health.insights.index', compact(
            'latestInsight',
            'pastInsights',
        ));
    }

    public function generate(Request $request): JsonResponse
    {
        $patient = Auth::user();

        try {
            $insight = $this->insightsService->generateInsights($patient, force: true);

            return response()->json([
                'success' => true,
                'insight' => [
                    'id' => $insight->id,
                    'summary' => $insight->summary,
                    'content' => $insight->content,
                    'created_at' => $insight->created_at->toDateTimeString(),
                    'expires_at' => $insight->expires_at->toDateTimeString(),
                    'is_fresh' => $insight->isFresh(),
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Health insights generation failed', [
                'user_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate insights. Please try again.',
            ], 500);
        }
    }
}
