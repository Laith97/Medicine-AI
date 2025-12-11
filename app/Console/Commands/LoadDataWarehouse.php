<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DataWarehouse\ETLService;

class LoadDataWarehouse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'etl:load-data-warehouse {--incremental : Run incremental load instead of full load}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load data into the analytics data warehouse';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $incremental = $this->option('incremental');

        $this->info($incremental ? 'Starting incremental ETL process...' : 'Starting full ETL process...');

        $etlService = app(ETLService::class);

        try {
            if ($incremental) {
                $etlService->runIncrementalLoad();
            } else {
                $etlService->runFullLoad();
            }

            $this->info('ETL process completed successfully.');
        } catch (\Exception $e) {
            $this->error('ETL process failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
