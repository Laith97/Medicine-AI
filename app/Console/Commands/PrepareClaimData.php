<?php

namespace App\Console\Commands;

use App\Models\Claim;
use App\Services\ClaimDataNormalizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PrepareClaimData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'claims:prepare-data
                            {--output= : Output file path (default: storage/app/claims/normalized_claims.json)}
                            {--batch-size=1000 : Number of claims to process per batch}
                            {--normalize-only : Only run normalization without extraction}
                            {--extract-only : Only run extraction without normalization}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare and normalize historical claims data for AI billing optimization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting claims data preparation...');

        $batchSize = (int) $this->option('batch-size');
        $outputPath = $this->option('output') ?: 'claims/normalized_claims.json';
        $normalizeOnly = $this->option('normalize-only');
        $extractOnly = $this->option('extract-only');

        $normalizationService = new ClaimDataNormalizationService();

        // Get total claims count
        $totalClaims = Claim::count();
        $this->info("Found {$totalClaims} claims to process");

        if ($totalClaims === 0) {
            $this->warn('No claims found in database. Please ensure claims data is populated.');
            return Command::SUCCESS;
        }

        $processedClaims = collect();

        // Process claims in batches
        Claim::chunk($batchSize, function (Collection $claims) use ($normalizationService, $normalizeOnly, $extractOnly, &$processedClaims) {
            $this->info("Processing batch of " . $claims->count() . " claims...");

            // Extract medical codes from text if not in extract-only mode
            if (!$extractOnly) {
                $this->info('Extracting medical codes from text...');
                $claims = $normalizationService->normalizeMedicalText($claims);
            }

            // Normalize denial codes if not in normalize-only mode
            if (!$normalizeOnly) {
                $this->info('Normalizing denial codes...');
                $claims = $normalizationService->normalizeDenialCodes($claims);
            }

            // Parse ERA/EOB data
            $this->info('Parsing ERA/EOB data...');
            $claims = $normalizationService->parseEraEobData($claims);

            $processedClaims = $processedClaims->merge($claims);
        });

        // Generate normalized data
        $this->info('Generating normalized data export...');
        $normalizedData = $normalizationService->generateNormalizedData($processedClaims);

        // Save to file
        $this->saveToFile($normalizedData, $outputPath);

        $this->info('Claims data preparation completed successfully!');
        $this->info("Processed {$processedClaims->count()} claims");
        $this->info("Output saved to: {$outputPath}");

        // Show summary statistics
        $this->displaySummary($processedClaims);

        return Command::SUCCESS;
    }

    /**
     * Save normalized data to file
     */
    private function saveToFile(array $data, string $path): void
    {
        // Ensure directory exists
        $directory = dirname($path);
        if ($directory !== '.' && !Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Save as JSON
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        Storage::put($path, $jsonData);

        $this->info("Data saved to {$path} (" . strlen($jsonData) . " bytes)");
    }

    /**
     * Display processing summary
     */
    private function displaySummary(Collection $claims): void
    {
        $this->info("\n=== Processing Summary ===");

        // Status breakdown
        $statusCounts = $claims->groupBy('claim_status')->map->count();
        $this->info('Claim Status Distribution:');
        foreach ($statusCounts as $status => $count) {
            $this->info("  {$status}: {$count}");
        }

        // Denial category breakdown
        $denialCounts = $claims->whereNotNull('normalized_denial_category')
                              ->groupBy('normalized_denial_category')
                              ->map->count();
        if ($denialCounts->isNotEmpty()) {
            $this->info('Normalized Denial Categories:');
            foreach ($denialCounts as $category => $count) {
                $this->info("  {$category}: {$count}");
            }
        }

        // Payment statistics
        $totalExpected = $claims->sum('expected_amount');
        $totalPaid = $claims->sum('paid_amount');
        $totalDifference = $claims->sum('payment_difference');

        $this->info('Payment Summary:');
        $this->info('  Total Expected: $' . number_format($totalExpected, 2));
        $this->info('  Total Paid: $' . number_format($totalPaid, 2));
        $this->info('  Total Difference: $' . number_format($totalDifference, 2));

        // Code extraction stats
        $withIcd10 = $claims->whereNotNull('icd10_codes')->where('icd10_codes', '!=', '[]')->count();
        $withCpt = $claims->whereNotNull('cpt_codes')->where('cpt_codes', '!=', '[]')->count();

        $this->info('Code Extraction:');
        $this->info("  Claims with ICD-10 codes: {$withIcd10}");
        $this->info("  Claims with CPT codes: {$withCpt}");
    }
}
