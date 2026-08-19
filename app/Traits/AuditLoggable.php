<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

trait AuditLoggable
{
    /**
     * Log an audit event for this model
     */
    public function logAuditEvent(string $action, array $data = [], ?int $userId = null): void
    {
        $userId = $userId ?? (Auth::check() ? Auth::id() : null);

        $logData = [
            'model_type' => static::class,
            'model_id' => $this->getKey(),
            'action' => $action,
            'user_id' => $userId,
            'ip_address' => \request()->ip(),
            'user_agent' => \request()->userAgent(),
            'metadata' => json_encode($data),
        ];

        // Log to Laravel log
        Log::info('Audit Event', $logData);

        // Store in audit table if it exists
        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')->insert($logData);
        }
    }

    /**
     * Boot the audit logging trait
     */
    public static function bootAuditLoggable()
    {
        static::created(function (Model $model) {
            $model->logAuditEvent('created', $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $changes = $model->getChanges();
            $original = $model->getOriginal();

            // Only log changes to sensitive fields
            $sensitiveFields = $model->getSensitiveFields();
            $sensitiveChanges = array_intersect_key($changes, array_flip($sensitiveFields));

            if (!empty($sensitiveChanges)) {
                $model->logAuditEvent('updated', [
                    'changes' => $sensitiveChanges,
                    'original' => array_intersect_key($original, $sensitiveChanges)
                ]);
            }
        });

        static::deleted(function (Model $model) {
            $model->logAuditEvent('deleted', $model->getAttributes());
        });
    }

    /**
     * Get sensitive fields that should be audited
     */
    protected function getSensitiveFields(): array
    {
        return property_exists($this, 'sensitiveFields') ? $this->sensitiveFields : [];
    }
}
