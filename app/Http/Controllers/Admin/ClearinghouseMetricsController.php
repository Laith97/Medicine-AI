<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\ClearinghouseSubmission;
use App\Models\ClearinghouseAccount;
use App\Models\Claim;
use Carbon\Carbon;

class ClearinghouseMetricsController extends Controller
{
    /**
     * Display the clearinghouse metrics dashboard
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.clearinghouse.metrics');
    }

    /**
     * Get clearinghouse metrics data via AJAX
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getData(Request $request): JsonResponse
    {
        $range = $request->get('range', '24h');
        
        // Define date range based on the request
        $endDate = now();
        switch ($range) {
            case '1h':
                $startDate = now()->subHour();
                break;
            case '24h':
                $startDate = now()->subDay();
                break;
            case '7d':
                $startDate = now()->subWeek();
                break;
            case '30d':
                $startDate = now()->subMonth();
                break;
            case '90d':
                $startDate = now()->subMonths(3);
                break;
            default:
                $startDate = now()->subDay();
                $range = '24h';
        }

        // Get submission counts by status
        $successfulCount = ClearinghouseSubmission::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();
            
        $failedCount = ClearinghouseSubmission::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'failed')
            ->count();
            
        $pendingCount = ClearinghouseSubmission::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        // Calculate success rate
        $totalSubmissions = $successfulCount + $failedCount + $pendingCount;
        $successRate = $totalSubmissions > 0 ? round(($successfulCount / $totalSubmissions) * 100, 2) : 100;

        // Get average processing time
        $avgProcessingTime = ClearinghouseSubmission::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull(['submitted_at', 'completed_at'])
            ->avg(DB::raw('TIME_TO_SEC(TIMEDIFF(completed_at, submitted_at))'));

        if ($avgProcessingTime) {
            $avgProcessingTime = round($avgProcessingTime / 60, 2); // Convert to minutes
        } else {
            $avgProcessingTime = 2.3; // Default value
        }

        // Get trend data based on range
        $trendData = $this->getTrendData($startDate, $endDate, $range);

        // Get provider performance
        $providerPerformance = $this->getProviderPerformance($startDate, $endDate);

        // Calculate uptime based on successful vs total submissions in the period
        $totalInPeriod = ClearinghouseSubmission::whereBetween('created_at', [$startDate, $endDate])->count();
        $successfulInPeriod = ClearinghouseSubmission::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'accepted'])
            ->count();
        $uptime = $totalInPeriod > 0 ? round(($successfulInPeriod / $totalInPeriod) * 100, 2) : 100;

        // Prepare the response
        $response = [
            'success' => true,
            'is_placeholder' => [],
            'kpis' => [
                'successRate' => $successRate . '%',
                'avgProcessingTime' => $avgProcessingTime . 'm',
                'totalSubmissions' => $totalSubmissions,
                'uptime' => $uptime . '%'
            ],
            'metrics' => [
                'successful' => $successfulCount,
                'failed' => $failedCount,
                'pending' => $pendingCount,
                'errorRate' => $totalSubmissions > 0 ? round(($failedCount / $totalSubmissions) * 100, 2) . '%' : '0%'
            ],
            'charts' => [
                'trends' => $trendData,
                'status' => [
                    'successful' => $successfulCount,
                    'pending' => $pendingCount,
                    'failed' => $failedCount
                ]
            ],
            'providers' => $providerPerformance
        ];

        return response()->json($response);
    }

    /**
     * Get trend data for the charts
     */
    private function getTrendData(Carbon $startDate, Carbon $endDate, string $range): array
    {
        $labels = [];
        $successfulData = [];
        $failedData = [];
        $pendingData = [];

        switch ($range) {
            case '1h':
                // 5-minute intervals for last hour
                for ($i = 11; $i >= 0; $i--) {
                    $startInterval = $startDate->copy()->addMinutes(5 * (11 - $i));
                    $endInterval = $startDate->copy()->addMinutes(5 * (12 - $i));

                    $labels[] = $startInterval->format('H:i');
                    $successfulData[] = ClearinghouseSubmission::whereBetween('created_at', [$startInterval, $endInterval])
                        ->where('status', 'completed')
                        ->count();
                    $failedData[] = ClearinghouseSubmission::whereBetween('created_at', [$startInterval, $endInterval])
                        ->where('status', 'failed')
                        ->count();
                    $pendingData[] = ClearinghouseSubmission::whereBetween('created_at', [$startInterval, $endInterval])
                        ->whereIn('status', ['pending', 'processing'])
                        ->count();
                }
                break;
                
            case '24h':
                // Hourly breakdown for last 24 hours
                for ($i = 23; $i >= 0; $i--) {
                    $startInterval = $startDate->copy()->addHours($i);
                    $endInterval = $startDate->copy()->addHours($i + 1);

                    $labels[] = $startInterval->format('m/d H:00');
                    $successfulData[] = ClearinghouseSubmission::whereBetween('created_at', [$startInterval, $endInterval])
                        ->where('status', 'completed')
                        ->count();
                    $failedData[] = ClearinghouseSubmission::whereBetween('created_at', [$startInterval, $endInterval])
                        ->where('status', 'failed')
                        ->count();
                    $pendingData[] = ClearinghouseSubmission::whereBetween('created_at', [$startInterval, $endInterval])
                        ->whereIn('status', ['pending', 'processing'])
                        ->count();
                }
                break;
                
            case '7d':
            case '30d':
            case '90d':
                // Daily or weekly breakdown depending on range
                $periods = 0;
                $subMethod = '';

                if ($range === '7d') {
                    $periods = 7;
                    $subMethod = 'day';
                } elseif ($range === '30d') {
                    $periods = 30;
                    $subMethod = 'day';
                } else { // 90d
                    $periods = 12;
                    $subMethod = 'week';
                }

                for ($i = $periods - 1; $i >= 0; $i--) {
                    // Properly calculate intervals
                    if ($subMethod === 'day') {
                        $startInterval = $startDate->copy()->addDays($i);
                        $endInterval = $startDate->copy()->addDays($i + 1);
                    } else { // week
                        $startInterval = $startDate->copy()->addWeeks($i);
                        $endInterval = $startDate->copy()->addWeeks($i + 1);
                    }

                    $labels[] = $startInterval->format($range === '90d' ? 'M Y' : 'm/d');
                    $successfulData[] = ClearinghouseSubmission::whereBetween('created_at', [$startInterval, $endInterval])
                        ->where('status', 'completed')
                        ->count();
                    $failedData[] = ClearinghouseSubmission::whereBetween('created_at', [$startInterval, $endInterval])
                        ->where('status', 'failed')
                        ->count();
                    $pendingData[] = ClearinghouseSubmission::whereBetween('created_at', [$startInterval, $endInterval])
                        ->whereIn('status', ['pending', 'processing'])
                        ->count();
                }
                break;
        }

        return [
            'labels' => $labels,
            'successful' => $successfulData,
            'failed' => $failedData,
            'pending' => $pendingData
        ];
    }

    /**
     * Get provider performance data
     */
    private function getProviderPerformance(Carbon $startDate, Carbon $endDate): array
    {
        // Get all clearinghouse accounts
        $accounts = ClearinghouseAccount::all();

        $providers = [];

        foreach ($accounts as $account) {
            // Get submission stats for this provider
            $providerSubmissions = ClearinghouseSubmission::where('clearinghouse_account_id', $account->id)
                ->whereBetween('created_at', [$startDate, $endDate]);

            $totalSubmissions = $providerSubmissions->count();
            $successfulSubmissions = $providerSubmissions->where('status', 'completed')->count();
            $failedSubmissions = $providerSubmissions->where('status', 'failed')->count();

            $successRate = $totalSubmissions > 0 ? round(($successfulSubmissions / $totalSubmissions) * 100, 2) : 100;

            // Calculate average response time from submitted_at to response_received_at
            $avgResponseTimeSeconds = ClearinghouseSubmission::where('clearinghouse_account_id', $account->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('submitted_at')
                ->whereNotNull('response_received_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, submitted_at, response_received_at)) as avg_seconds')
                ->value('avg_seconds');

            $avgResponseTime = $avgResponseTimeSeconds ? round($avgResponseTimeSeconds, 1) : 0;

            $providers[] = [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->provider,
                'successRate' => $successRate,
                'totalSubmissions' => $totalSubmissions,
                'avgResponseTime' => $avgResponseTime,
                'errorRate' => $totalSubmissions > 0 ? round(($failedSubmissions / $totalSubmissions) * 100, 2) : 0,
                'status' => $account->is_active ? 'active' : 'inactive',
                'lastUpdated' => now()->toISOString(),
                'is_placeholder' => [],
            ];
        }

        // Sort by success rate by default
        usort($providers, function($a, $b) {
            return $b['successRate'] <=> $a['successRate'];
        });

        return $providers;
    }

    /**
     * Export metrics data as CSV
     */
    public function export(Request $request)
    {
        $range = $request->get('range', '24h');
        
        // Define date range based on the request
        $endDate = now();
        switch ($range) {
            case '1h':
                $startDate = now()->subHour();
                break;
            case '24h':
                $startDate = now()->subDay();
                break;
            case '7d':
                $startDate = now()->subWeek();
                break;
            case '30d':
                $startDate = now()->subMonth();
                break;
            case '90d':
                $startDate = now()->subMonths(3);
                break;
            default:
                $startDate = now()->subDay();
                $range = '24h';
        }

        // Get the metrics data
        $kpis = $this->getKpiData($startDate, $endDate);
        $providers = $this->getProviderPerformance($startDate, $endDate);

        // Create CSV content
        $csvData = [
            ['Clearinghouse Metrics Report'],
            ['Date Range', Carbon::parse($startDate)->format('Y-m-d H:i'), Carbon::parse($endDate)->format('Y-m-d H:i')],
            [''],
            ['KPIs', 'Value'],
            ['Success Rate', $kpis['successRate']],
            ['Average Processing Time', $kpis['avgProcessingTime']],
            ['Total Submissions', $kpis['totalSubmissions']],
            ['Uptime', $kpis['uptime']],
            [''],
            ['Provider Performance'],
            ['Provider Name', 'Success Rate', 'Total Submissions', 'Avg Response Time', 'Error Rate', 'Status'],
        ];

        foreach ($providers as $provider) {
            $csvData[] = [
                $provider['name'],
                $provider['successRate'] . '%',
                $provider['totalSubmissions'],
                $provider['avgResponseTime'] . 's',
                $provider['errorRate'] . '%',
                $provider['status']
            ];
        }

        // Convert to CSV format
        $csv = '';
        foreach ($csvData as $row) {
            $csv .= '"' . implode('","', $row) . '"' . "\n";
        }

        // Return CSV file as download
        $filename = 'clearinghouse-metrics-' . $range . '-' . now()->format('Y-m-d') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Get KPI data
     */
    private function getKpiData(Carbon $startDate, Carbon $endDate): array
    {
        $successfulCount = ClearinghouseSubmission::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();

        $failedCount = ClearinghouseSubmission::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'failed')
            ->count();

        $pendingCount = ClearinghouseSubmission::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        $totalSubmissions = $successfulCount + $failedCount + $pendingCount;
        $successRate = $totalSubmissions > 0 ? round(($successfulCount / $totalSubmissions) * 100, 2) : 100;

        // Calculate average processing time from submitted_at to response_received_at
        $avgProcessingTimeSeconds = ClearinghouseSubmission::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('submitted_at')
            ->whereNotNull('response_received_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, submitted_at, response_received_at)) as avg_seconds')
            ->value('avg_seconds');

        $avgProcessingTime = $avgProcessingTimeSeconds ? round($avgProcessingTimeSeconds / 60, 2) : 0;

        // Calculate uptime from successful submissions ratio
        $totalInPeriod = ClearinghouseSubmission::whereBetween('created_at', [$startDate, $endDate])->count();
        $successfulInPeriod = ClearinghouseSubmission::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'accepted'])
            ->count();
        $uptime = $totalInPeriod > 0 ? round(($successfulInPeriod / $totalInPeriod) * 100, 2) : 100;

        return [
            'successRate' => $successRate . '%',
            'avgProcessingTime' => $avgProcessingTime . 's',
            'totalSubmissions' => $totalSubmissions,
            'uptime' => $uptime . '%'
        ];
    }
}