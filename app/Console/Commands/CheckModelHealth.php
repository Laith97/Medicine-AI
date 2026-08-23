<?php

namespace App\Console\Commands;

use App\Services\PredictiveAnalyticsService;
use Illuminate\Console\Command;

class CheckModelHealth extends Command
{
    protected $signature = 'models:health {--json : output json}';
    protected $description = 'Check ML model health, adequacy and metrics';

    public function __construct(private PredictiveAnalyticsService $svc)
    {
        parent::__construct();
    }

    public function handle()
    {
        $health = $this->svc->getModelHealth();
        if ($this->option('json')) {
            $this->line(json_encode($health, JSON_PRETTY_PRINT));
            return $health['adequacy']['adequate'] ? 0 : 2;
        }
        $this->info('=== Predictive Analytics Health ===');
        $this->table(['Check','Value'], [
            ['Adequate for ML', $health['adequacy']['adequate'] ? 'YES' : 'NO - using rule-based fallback'],
            ['Version', $health['adequacy']['model_version']],
            ['Total historical', $health['adequacy']['total_appointments']],
            ['No-show count', $health['adequacy']['no_show_count'] . ' ('.round($health['adequacy']['no_show_rate']*100,1).'%)'],
            ['Hospitalization', $health['adequacy']['hospitalization_count'] . ' ('.round($health['adequacy']['hospitalization_rate']*100,1).'%)'],
            ['NS model file', $health['adequacy']['no_show_model_exists'] ? 'exists' : 'MISSING'],
            ['HOSP model file', $health['adequacy']['hosp_model_exists'] ? 'exists' : 'MISSING'],
        ]);
        if ($health['no_show_meta']) {
            $this->info('No-show meta: '.json_encode($health['no_show_meta']['metrics'] ?? $health['no_show_meta']));
        }
        if ($health['hospitalization_meta']) {
            $this->info('Hosp meta: '.json_encode($health['hospitalization_meta']['metrics'] ?? $health['hospitalization_meta']));
        }
        if (!$health['adequacy']['adequate']) {
            $this->warn('Add more historical appointments with was_hospitalized and missed/no_show statuses, then run: php artisan predictions:retrain');
        }
        return $health['adequacy']['adequate'] ? 0 : 2;
    }
}
