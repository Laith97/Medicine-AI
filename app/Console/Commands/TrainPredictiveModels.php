<?php

namespace App\Console\Commands;

use App\Services\PredictiveAnalyticsService;
use Illuminate\Console\Command;

class TrainPredictiveModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'models:train';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Train the predictive analytics ML models';

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
        $this->info('Starting ML model training...');

        try {
            $this->analyticsService->trainModels();
            $this->info('ML model training completed successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to train models: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}