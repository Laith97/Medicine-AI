<?php

namespace App\Console\Commands;

use App\Services\PredictiveAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetrainModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:retrain';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrain the ML models for risk predictions';

    private PredictiveAnalyticsService $analyticsService;

    public function __construct(PredictiveAnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting model retraining...');

        try {
            $this->analyticsService->trainModels();

            $this->info('Model retraining completed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Model retraining failed: ' . $e->getMessage());
            Log::error('Model retraining failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
