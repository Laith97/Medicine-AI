<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LandingPageVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'visitor_ip',
        'user_agent',
        'referrer_url',
        'page_url',
        'country',
        'city',
        'device_type',
        'browser',
        'os',
        'session_duration',
        'visited_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'session_duration' => 'integer',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('visited_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('visited_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('visited_at', now()->month)
                    ->whereYear('visited_at', now()->year);
    }

    public function scopeLastDays($query, $days = 30)
    {
        return $query->where('visited_at', '>=', now()->subDays($days));
    }

    public static function recordVisit($doctorId, $request)
    {
        // Don't record visits from the doctor themselves
        if (auth()->check() && auth()->user()->doctor && auth()->user()->doctor->id == $doctorId) {
            return null;
        }

        $userAgent = $request->userAgent();
        $deviceInfo = static::parseUserAgent($userAgent);

        return static::create([
            'doctor_id' => $doctorId,
            'visitor_ip' => $request->ip(),
            'user_agent' => $userAgent,
            'referrer_url' => $request->header('referer'),
            'page_url' => $request->fullUrl(),
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'os' => $deviceInfo['os'],
            'visited_at' => now(),
        ]);
    }

    public static function getDailyVisitsForDoctor($doctorId, $days = 30)
    {
        return static::select(
                DB::raw('DATE(visited_at) as date'),
                DB::raw('COUNT(*) as visits'),
                DB::raw('COUNT(DISTINCT visitor_ip) as unique_visitors')
            )
            ->where('doctor_id', $doctorId)
            ->where('visited_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw('DATE(visited_at)'))
            ->orderBy('date')
            ->get();
    }

    public static function getTopReferrers($doctorId, $limit = 10)
    {
        return static::select('referrer_url', DB::raw('COUNT(*) as visits'))
            ->where('doctor_id', $doctorId)
            ->whereNotNull('referrer_url')
            ->where('referrer_url', '!=', '')
            ->groupBy('referrer_url')
            ->orderByDesc('visits')
            ->limit($limit)
            ->get();
    }

    public static function getDeviceStats($doctorId)
    {
        return static::select('device_type', DB::raw('COUNT(*) as visits'))
            ->where('doctor_id', $doctorId)
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->orderByDesc('visits')
            ->get();
    }

    public static function getBrowserStats($doctorId)
    {
        return static::select('browser', DB::raw('COUNT(*) as visits'))
            ->where('doctor_id', $doctorId)
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();
    }

    private static function parseUserAgent($userAgent)
    {
        $deviceType = 'desktop';
        $browser = 'Unknown';
        $os = 'Unknown';

        // Detect device type
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            if (preg_match('/iPad/', $userAgent)) {
                $deviceType = 'tablet';
            } else {
                $deviceType = 'mobile';
            }
        } elseif (preg_match('/Tablet/', $userAgent)) {
            $deviceType = 'tablet';
        }

        // Detect browser
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches)) {
            if (!preg_match('/Chrome/', $userAgent)) {
                $browser = 'Safari';
            }
        } elseif (preg_match('/Edge\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Edge';
        } elseif (preg_match('/Opera\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Opera';
        }

        // Detect OS
        if (preg_match('/Windows NT ([0-9.]+)/', $userAgent, $matches)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X ([0-9._]+)/', $userAgent, $matches)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android ([0-9.]+)/', $userAgent, $matches)) {
            $os = 'Android';
        } elseif (preg_match('/iPhone OS ([0-9._]+)/', $userAgent, $matches)) {
            $os = 'iOS';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
        ];
    }
}
