<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;

class StructuredLoggingService
{
    protected Logger $securityLogger;
    protected Logger $performanceLogger;
    protected Logger $businessLogger;
    protected Logger $errorLogger;

    public function __construct()
    {
        $this->initializeLoggers();
    }

    /**
     * Initialize specialized loggers
     */
    protected function initializeLoggers(): void
    {
        // Security logger for authentication and authorization events
        $this->securityLogger = new Logger('security');
        $this->securityLogger->pushHandler(new StreamHandler(storage_path('logs/security.log'), Logger::INFO));
        $this->securityLogger->pushProcessor(new WebProcessor());
        $this->securityLogger->pushProcessor(function ($record) {
            $record['extra']['user_id'] = Auth::id();
            $record['extra']['ip'] = Request::ip();
            $record['extra']['user_agent'] = Request::userAgent();
            return $record;
        });

        // Performance logger for response times and resource usage
        $this->performanceLogger = new Logger('performance');
        $this->performanceLogger->pushHandler(new StreamHandler(storage_path('logs/performance.log'), Logger::INFO));
        $this->performanceLogger->pushProcessor(new MemoryUsageProcessor());
        $this->performanceLogger->pushProcessor(new MemoryPeakUsageProcessor());
        $this->performanceLogger->pushProcessor(new WebProcessor());

        // Business logger for business logic events
        $this->businessLogger = new Logger('business');
        $this->businessLogger->pushHandler(new StreamHandler(storage_path('logs/business.log'), Logger::INFO));
        $this->businessLogger->pushProcessor(function ($record) {
            $record['extra']['user_id'] = Auth::id();
            $record['extra']['session_id'] = session()->getId();
            return $record;
        });

        // Error logger for structured error reporting
        $this->errorLogger = new Logger('error');
        $this->errorLogger->pushHandler(new StreamHandler(storage_path('logs/error.log'), Logger::ERROR));
        $this->errorLogger->pushProcessor(new WebProcessor());
        $this->errorLogger->pushProcessor(function ($record) {
            $record['extra']['trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
            $record['extra']['server'] = [
                'host' => gethostname(),
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
            ];
            return $record;
        });
    }

    /**
     * Log security events
     */
    public function logSecurityEvent(string $event, array $context = [], string $level = 'info'): void
    {
        $context = array_merge($context, [
            'event_type' => 'security',
            'event_name' => $event,
            'timestamp' => now()->toISOString(),
        ]);

        $this->securityLogger->log($level, "Security event: {$event}", $context);
    }

    /**
     * Log authentication events
     */
    public function logAuthentication(string $action, bool $success = true, array $context = []): void
    {
        $context = array_merge($context, [
            'action' => $action,
            'success' => $success,
            'timestamp' => now()->toISOString(),
        ]);

        $level = $success ? 'info' : 'warning';
        $this->logSecurityEvent("authentication_{$action}", $context, $level);
    }

    /**
     * Log authorization events
     */
    public function logAuthorization(string $resource, string $action, bool $allowed = true, array $context = []): void
    {
        $context = array_merge($context, [
            'resource' => $resource,
            'action' => $action,
            'allowed' => $allowed,
            'timestamp' => now()->toISOString(),
        ]);

        $level = $allowed ? 'info' : 'warning';
        $this->logSecurityEvent("authorization_{$action}", $context, $level);
    }

    /**
     * Log performance metrics
     */
    public function logPerformance(string $operation, float $duration, array $context = []): void
    {
        $context = array_merge($context, [
            'operation' => $operation,
            'duration_ms' => round($duration, 2),
            'timestamp' => now()->toISOString(),
        ]);

        // Log slow operations as warnings
        $level = $duration > 1000 ? 'warning' : 'info';

        $this->performanceLogger->log($level, "Performance: {$operation}", $context);
    }

    /**
     * Log business events
     */
    public function logBusinessEvent(string $event, array $context = [], string $level = 'info'): void
    {
        $context = array_merge($context, [
            'event_type' => 'business',
            'event_name' => $event,
            'timestamp' => now()->toISOString(),
        ]);

        $this->businessLogger->log($level, "Business event: {$event}", $context);
    }

    /**
     * Log appointment events
     */
    public function logAppointmentEvent(string $action, int $appointmentId, array $context = []): void
    {
        $context = array_merge($context, [
            'appointment_id' => $appointmentId,
            'action' => $action,
        ]);

        $this->logBusinessEvent("appointment_{$action}", $context);
    }

    /**
     * Log prescription events
     */
    public function logPrescriptionEvent(string $action, int $prescriptionId, array $context = []): void
    {
        $context = array_merge($context, [
            'prescription_id' => $prescriptionId,
            'action' => $action,
        ]);

        $this->logBusinessEvent("prescription_{$action}", $context);
    }

    /**
     * Log billing events
     */
    public function logBillingEvent(string $action, array $context = []): void
    {
        $context = array_merge($context, [
            'action' => $action,
        ]);

        $this->logBusinessEvent("billing_{$action}", $context);
    }

    /**
     * Log structured errors
     */
    public function logError(\Throwable $exception, array $context = [], string $level = 'error'): void
    {
        $context = array_merge($context, [
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'exception_code' => $exception->getCode(),
            'timestamp' => now()->toISOString(),
        ]);

        $this->errorLogger->log($level, "Error: {$exception->getMessage()}", $context);

        // Also log to Laravel's default logger for compatibility
        Log::error("Structured error logged", $context);
    }

    /**
     * Log API requests
     */
    public function logApiRequest(string $method, string $endpoint, int $statusCode, float $duration, array $context = []): void
    {
        $context = array_merge($context, [
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'duration_ms' => round($duration, 2),
            'timestamp' => now()->toISOString(),
        ]);

        $level = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'info');
        $this->performanceLogger->log($level, "API Request: {$method} {$endpoint}", $context);
    }

    /**
     * Log database queries (for slow query monitoring)
     */
    public function logDatabaseQuery(string $query, float $duration, array $context = []): void
    {
        if ($duration < 100) { // Only log queries slower than 100ms
            return;
        }

        $context = array_merge($context, [
            'query' => $query,
            'duration_ms' => round($duration, 2),
            'timestamp' => now()->toISOString(),
        ]);

        $level = $duration > 1000 ? 'warning' : 'info';
        $this->performanceLogger->log($level, "Slow database query", $context);
    }

    /**
     * Log cache operations
     */
    public function logCacheOperation(string $operation, string $key, bool $success = true, array $context = []): void
    {
        $context = array_merge($context, [
            'operation' => $operation,
            'key' => $key,
            'success' => $success,
            'timestamp' => now()->toISOString(),
        ]);

        $level = $success ? 'debug' : 'warning';
        $this->performanceLogger->log($level, "Cache operation: {$operation}", $context);
    }

    /**
     * Log external API calls
     */
    public function logExternalApiCall(string $service, string $endpoint, int $statusCode, float $duration, array $context = []): void
    {
        $context = array_merge($context, [
            'service' => $service,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'duration_ms' => round($duration, 2),
            'timestamp' => now()->toISOString(),
        ]);

        $level = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'info');
        $this->performanceLogger->log($level, "External API call: {$service}", $context);
    }

    /**
     * Get log statistics for monitoring
     */
    public function getLogStatistics(string $type = 'all', int $hours = 24): array
    {
        $stats = [];

        try {
            $logFiles = [
                'security' => storage_path('logs/security.log'),
                'performance' => storage_path('logs/performance.log'),
                'business' => storage_path('logs/business.log'),
                'error' => storage_path('logs/error.log'),
            ];

            foreach ($logFiles as $logType => $filePath) {
                if ($type !== 'all' && $type !== $logType) {
                    continue;
                }

                if (!file_exists($filePath)) {
                    $stats[$logType] = ['count' => 0, 'errors' => 0, 'warnings' => 0];
                    continue;
                }

                $content = file_get_contents($filePath);
                $lines = explode("\n", $content);

                $count = 0;
                $errors = 0;
                $warnings = 0;

                foreach ($lines as $line) {
                    if (empty($line)) continue;

                    $count++;
                    if (strpos($line, '"level":40') !== false || strpos($line, '"level_name":"ERROR"') !== false) {
                        $errors++;
                    }
                    if (strpos($line, '"level":30') !== false || strpos($line, '"level_name":"WARNING"') !== false) {
                        $warnings++;
                    }
                }

                $stats[$logType] = [
                    'count' => $count,
                    'errors' => $errors,
                    'warnings' => $warnings,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Failed to get log statistics', ['error' => $e->getMessage()]);
        }

        return $stats;
    }

    /**
     * Clean up old log files
     */
    public function cleanupOldLogs(int $daysToKeep = 30): void
    {
        try {
            $logDirectory = storage_path('logs');
            $files = glob($logDirectory . '/*.log');

            foreach ($files as $file) {
                if (filemtime($file) < strtotime("-{$daysToKeep} days")) {
                    unlink($file);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old logs', ['error' => $e->getMessage()]);
        }
    }
}
