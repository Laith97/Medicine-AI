<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Rubix\ML\Classifiers\RandomForest;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PredictiveAnalyticsService
{
    private FeatureExtractor $featureExtractor;
    public const MODEL_VERSION = '1.0.0';
    public const MIN_APPOINTMENTS = 50;
    public const MIN_NO_SHOW_RATE = 0.02;
    public const MIN_HOSPITALIZATION_RATE = 0.05;

    public function __construct(FeatureExtractor $featureExtractor)
    {
        $this->featureExtractor = $featureExtractor;
    }

    private function getNoShowModelPath(): string
    {
        return config('predictive-analytics.models.no_show.path', 'app/models/no_show_model.rbx');
    }

    private function getHospitalizationModelPath(): string
    {
        return config('predictive-analytics.models.hospitalization.path', 'app/models/hospitalization_model.rbx');
    }

    private function getMetaPath(string $modelPath): string
    {
        return str_replace('.rbx', '_meta.json', $modelPath);
    }

    public function trainModels(): array
    {
        $appointments = Appointment::with(['patient'])
            ->whereNotNull('patient_id')
            ->where('appointment_date', '<', now())
            ->get();

        if ($appointments->count() < 10) {
            Log::warning('ML training skipped: not enough historical data', ['count' => $appointments->count()]);
            return ['status' => 'skipped', 'reason' => 'insufficient_data', 'count' => $appointments->count()];
        }

        $noShowSamples = []; $noShowLabels = [];
        $hospSamples = []; $hospLabels = [];

        foreach ($appointments as $appt) {
            $patient = $appt->patient;
            if (!$patient) continue;
            $features = $this->featureExtractor->extractFeatures($patient, $appt);
            if (!$this->featureExtractor->validateFeatures($features)) continue;

            // Production label: true no-show status only
            $noShowLabels[] = in_array($appt->status, ['missed','no_show'], true) ? '1' : '0';
            $noShowSamples[] = $features;

            // Production hospitalization label: prefer explicit flag, fallback to diagnosis severity
            $hospLabel = $this->resolveHospitalizationLabel($appt, $patient);
            $hospLabels[] = $hospLabel;
            $hospSamples[] = $features;
        }

        $results = [];

        // Balance helper: oversample minority to avoid 0% positive collapse
        $results['no_show'] = $this->trainAndPersist('no_show', $noShowSamples, $noShowLabels);
        $results['hospitalization'] = $this->trainAndPersist('hospitalization', $hospSamples, $hospLabels);

        Log::info('ML training completed', $results);
        return $results;
    }

    private function resolveHospitalizationLabel(Appointment $appt, User $patient): string
    {
        // 1) Explicit ground truth if available
        if (!is_null($appt->was_hospitalized)) return $appt->was_hospitalized ? '1' : '0';
        // 2) Diagnosis severity critical / requires_hospitalization
        $hasCritical = \App\Models\Diagnosis::where('patient_id', $patient->id)
            ->where(function($q){ $q->where('requires_hospitalization', true)->orWhere('severity','critical'); })->exists();
        if ($hasCritical) return '1';
        // 3) Fallback to chronic proxy (for backward compat, but weighted lower)
        return $this->featureExtractor->hasHighRiskCondition($patient) ? '1' : '0';
    }

    private function trainAndPersist(string $type, array $samples, array $labels): array
    {
        $total = count($labels);
        $positives = count(array_filter($labels, fn($l)=>$l==='1'));
        $rate = $total ? $positives / $total : 0;

        if ($total < 10) return ['status'=>'skipped','total'=>$total,'positives'=>$positives];
        if ($positives === 0 || $positives === $total) {
            Log::warning("ML {$type} training: single-class data, using rule-based fallback", compact('total','positives'));
            // still save a dummy model? skip to avoid broken classifier
            return ['status'=>'skipped_single_class','total'=>$total,'positives'=>$positives,'rate'=>round($rate,4)];
        }

        // Oversample minority if <15% to help RandomForest
        if ($rate < 0.15 || $rate > 0.85) {
            [$samples, $labels] = $this->balanceDataset($samples, $labels);
        }

        $dataset = new Labeled($samples, $labels);
        // Compatible with rubix/ml 2.x where __construct(?Learner $base, int $estimators...)
        try {
            $classifier = new RandomForest(null, 100, 0.5, true);
        } catch (\ArgumentCountError $e) {
            $classifier = new RandomForest();
        }

        try {
            $classifier->train($dataset);
        } catch (\Exception $e) {
            Log::error("ML {$type} training failed", ['error'=>$e->getMessage()]);
            return ['status'=>'failed','error'=>$e->getMessage()];
        }

        // Evaluate with stratified holdout 20% if enough data
        $metrics = $this->evaluate($classifier, $samples, $labels);

        $path = $type === 'no_show' ? $this->getNoShowModelPath() : $this->getHospitalizationModelPath();
        $fullPath = storage_path($path);
        @mkdir(dirname($fullPath), 0755, true);
        // Atomic write
        $tmp = $fullPath.'.tmp';
        file_put_contents($tmp, serialize($classifier));
        rename($tmp, $fullPath);

        $meta = [
            'version' => self::MODEL_VERSION,
            'type' => $type,
            'trained_at' => now()->toIso8601String(),
            'total' => $total,
            'positives' => $positives,
            'rate' => round($rate,4),
            'balanced_total' => count($labels),
            'metrics' => $metrics,
            'features' => FeatureExtractor::FEATURE_NAMES,
        ];
        file_put_contents(storage_path($this->getMetaPath($path)), json_encode($meta, JSON_PRETTY_PRINT));

        return ['status'=>'trained','meta'=>$meta];
    }

    private function balanceDataset(array $samples, array $labels): array
    {
        $posIdx = array_keys(array_filter($labels, fn($l)=>$l==='1'));
        $negIdx = array_keys(array_filter($labels, fn($l)=>$l==='0'));
        $minor = count($posIdx) < count($negIdx) ? $posIdx : $negIdx;
        $majorCount = max(count($posIdx), count($negIdx));
        $minorCount = count($minor);
        if ($minorCount === 0) return [$samples, $labels];
        $repeats = (int) ceil($majorCount / max(1,$minorCount)) -1;
        $repeats = min(5, $repeats); // cap
        for ($r=0;$r<$repeats;$r++) {
            foreach ($minor as $i) { $samples[]=$samples[$i]; $labels[]=$labels[$i]; }
        }
        return [$samples, $labels];
    }

    private function evaluate(RandomForest $classifier, array $samples, array $labels): array
    {
        if (count($samples) < 20) return ['skipped'=>'too_few'];
        $n = count($samples);
        $hold = (int) ($n * 0.2);
        $indices = array_rand($samples, $hold);
        if (!is_array($indices)) $indices = [$indices];
        $testSamples=[]; $testLabels=[];
        foreach ($indices as $i){ $testSamples[]=$samples[$i]; $testLabels[]=$labels[$i]; }
        try {
            $dataset = new Unlabeled($testSamples);
            $preds = $classifier->predict($dataset);
            $correct = 0; foreach ($preds as $k=>$p) if ($p == $testLabels[$k]) $correct++;
            $acc = $hold ? $correct / $hold : 0;
            return ['accuracy'=>round($acc,4),'holdout'=>$hold,'correct'=>$correct];
        } catch (\Exception $e) {
            return ['error'=>$e->getMessage()];
        }
    }

    public function predictRisks(User $patient, Appointment $appointment): array
    {
        $features = $this->featureExtractor->extractFeatures($patient, $appointment);
        return $this->predictRisksFromFeatures($features);
    }

    public function predictRisksFromFeatures(array $features): array
    {
        if (!$this->featureExtractor->validateFeatures($features)) {
            Log::warning('ML predict: invalid features, using defaults', ['features'=>$features]);
            $features = array_slice(array_merge($features, [0,0,365,1.0,30,0,0,0,7]),0,9);
        }

        Log::info('ML Risk Assessment - Features', ['features'=>$features]);

        $mlNoShow = $this->predictNoShowRisk($features);
        $mlHosp = $this->predictHospitalizationRisk($features);
        $check = $this->checkTrainingDataAdequacy();

        $useFallback = !$check['adequate'] || ($mlNoShow < 0.001 && $mlHosp < 0.001);
        $method = $useFallback ? 'rule_based' : 'ml';

        if ($useFallback) {
            $rb = $this->calculateRuleBasedRisks($features);
            $noShow = $rb['no_show_risk']; $hosp = $rb['hospitalization_risk'];
            $conf = $this->estimateRuleConfidence($check);
        } else {
            $noShow = $mlNoShow; $hosp = $mlHosp;
            $conf = $this->estimateMlConfidence($mlNoShow, $mlHosp, $check);
        }

        // Calibrate: ensure 0-1 and small epsilon
        $noShow = max(0.01, min(0.99, round($noShow,4)));
        $hosp = max(0.01, min(0.99, round($hosp,4)));
        $conf = max(0.3, min(0.95, round($conf,2)));

        Log::info('ML Final', compact('method','noShow','hosp','conf','check'));
        return [
            'no_show_risk' => $noShow,
            'hospitalization_risk' => $hosp,
            'prediction_method' => $method,
            'confidence' => $conf,
            'model_version' => $this->getModelVersion(),
        ];
    }

    private function predictNoShowRisk(array $features): float
    {
        try {
            $path = storage_path($this->getNoShowModelPath());
            if (!file_exists($path)) return 0.0;
            $clf = unserialize(file_get_contents($path));
            $ds = new Unlabeled([$features]);
            if (method_exists($clf,'proba')) {
                $p = $clf->proba($ds);
                // proba returns associative [ '0'=>0.7,'1'=>0.3 ]
                if (isset($p[0]['1'])) return (float)$p[0]['1'];
                if (isset($p[0][1])) return (float)$p[0][1];
            }
            $pred = $clf->predict($ds);
            return $pred[0] === '1' ? 0.85 : 0.15;
        } catch (\Exception $e) { Log::debug('no_show predict fail', ['e'=>$e->getMessage()]); return 0.0; }
    }

    private function predictHospitalizationRisk(array $features): float
    {
        try {
            $path = storage_path($this->getHospitalizationModelPath());
            if (!file_exists($path)) return 0.0;
            $clf = unserialize(file_get_contents($path));
            $ds = new Unlabeled([$features]);
            if (method_exists($clf,'proba')) {
                $p = $clf->proba($ds);
                if (isset($p[0]['1'])) return (float)$p[0]['1'];
                if (isset($p[0][1])) return (float)$p[0][1];
            }
            $pred = $clf->predict($ds);
            return $pred[0] === '1' ? 0.85 : 0.15;
        } catch (\Exception $e) { Log::debug('hosp predict fail', ['e'=>$e->getMessage()]); return 0.0; }
    }

    public function checkTrainingDataAdequacy(): array
    {
        $appts = Appointment::whereNotNull('patient_id')->where('appointment_date','<',now())->get();
        $total = $appts->count();
        $noShow=0; $hosp=0;
        $hasWasHospColumn = \Illuminate\Support\Facades\Schema::hasColumn('appointments', 'was_hospitalized');
        foreach ($appts as $a){
            if (in_array($a->status,['missed','no_show'],true)) $noShow++;
            // production ground truth - safe if column missing pre-migrate
            $counted = false;
            if ($hasWasHospColumn) {
                try { if (!is_null($a->was_hospitalized) && $a->was_hospitalized) { $hosp++; $counted=true; } } catch (\Exception $e) {}
            }
            if (!$counted && $a->patient && $this->featureExtractor->hasHospitalizationHistory($a->patient)) $hosp++;
        }
        $adequate = $total >= self::MIN_APPOINTMENTS
            && ($noShow / max(1,$total)) >= self::MIN_NO_SHOW_RATE
            && ($hosp / max(1,$total)) >= self::MIN_HOSPITALIZATION_RATE
            && $noShow >= 3 && $hosp >= 3; // absolute minimum positives

        return [
            'adequate'=>$adequate,
            'total_appointments'=>$total,
            'no_show_count'=>$noShow,
            'high_risk_count'=>$hosp, // keep key for blade compat
            'hospitalization_count'=>$hosp,
            'no_show_rate'=> $total? round($noShow/$total,4):0,
            'high_risk_rate'=> $total? round($hosp/$total,4):0,
            'hospitalization_rate'=> $total? round($hosp/$total,4):0,
            'model_version'=>$this->getModelVersion(),
            'no_show_model_exists'=>file_exists(storage_path($this->getNoShowModelPath())),
            'hosp_model_exists'=>file_exists(storage_path($this->getHospitalizationModelPath())),
        ];
    }

    private function estimateMlConfidence(float $ns, float $h, array $check): float
    {
        if (!$check['adequate']) return 0.55;
        $spread = abs($ns - $h) > 0.05 ? 0.75 : 0.65;
        $pos = min($check['no_show_count'], $check['hospitalization_count']);
        $bonus = $pos > 20 ? 0.15 : ($pos > 10 ? 0.08 : 0);
        return min(0.90, $spread + $bonus);
    }

    private function estimateRuleConfidence(array $check): float
    {
        if ($check['total_appointments'] < 10) return 0.45;
        if (!$check['adequate']) return 0.55;
        return 0.65;
    }

    private function getModelVersion(): string
    {
        $metaPath = storage_path($this->getMetaPath($this->getNoShowModelPath()));
        if (file_exists($metaPath)) {
            $j=json_decode(file_get_contents($metaPath), true);
            return $j['version'] ?? self::MODEL_VERSION;
        }
        return self::MODEL_VERSION;
    }

    public function getModelHealth(): array
    {
        $check = $this->checkTrainingDataAdequacy();
        $nsMeta = file_exists(storage_path($this->getMetaPath($this->getNoShowModelPath()))) ? json_decode(file_get_contents(storage_path($this->getMetaPath($this->getNoShowModelPath()))), true) : null;
        $hMeta = file_exists(storage_path($this->getMetaPath($this->getHospitalizationModelPath()))) ? json_decode(file_get_contents(storage_path($this->getMetaPath($this->getHospitalizationModelPath()))), true) : null;
        return [
            'adequacy'=>$check,
            'no_show_meta'=>$nsMeta,
            'hospitalization_meta'=>$hMeta,
            'models_exist'=> $check['no_show_model_exists'] && $check['hosp_model_exists'],
        ];
    }

    // Keep original name for blade compat, delegate
    private function calculateRuleBasedRisks(array $features): array
    {
        [$noShowCount,$cancellationCount,$lastVisitDays,$visitFreq,$age,$gender,$chronic,$meds,$leadTime] = array_pad($features,9,0);
        $ns=0.0;
        if ($noShowCount>0) $ns += min($noShowCount*0.18, 0.45);
        if ($cancellationCount>0) $ns += min($cancellationCount*0.09, 0.25);
        if ($lastVisitDays>365) $ns+=0.12; elseif($lastVisitDays>180) $ns+=0.06;
        if ($age<25 || $age>70) $ns+=0.04;
        if ($leadTime<2) $ns+=0.08;
        if ($leadTime>30) $ns+=0.05;
        if ($visitFreq<1) $ns+=0.06;

        $h=0.0;
        if ($chronic>=3) $h+=0.35; elseif($chronic>=2) $h+=0.22; elseif($chronic>=1) $h+=0.12;
        if ($meds>=5) $h+=0.18; elseif($meds>=3) $h+=0.09;
        if ($age>65) $h+=0.18; elseif($age>50) $h+=0.09;
        if ($gender===1) $h+=0.04;
        if ($visitFreq>12) $h+=0.12;
        if ($lastVisitDays>365) $h+=0.04;

        return ['no_show_risk'=>min(0.95,max(0.05,$ns)), 'hospitalization_risk'=>min(0.95,max(0.05,$h))];
    }
}
