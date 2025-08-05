<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CheckDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check database schema and data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=== Checking Users Table Schema ===");
        
        $columns = Schema::getColumnListing('users');
        $this->info("Users table columns:");
        foreach ($columns as $column) {
            $this->info("- {$column}");
        }

        // Check if sub-user columns exist
        $subUserColumns = ['parent_user_id', 'sub_user_role', 'is_sub_user'];
        $this->info("\n=== Sub-User Columns Check ===");
        foreach ($subUserColumns as $column) {
            if (in_array($column, $columns)) {
                $this->info("✅ {$column} exists");
            } else {
                $this->error("❌ {$column} missing");
            }
        }

        // Check permissions table
        $this->info("\n=== Checking Permissions Table ===");
        if (Schema::hasTable('permissions')) {
            $permissionCount = DB::table('permissions')->count();
            $this->info("✅ Permissions table exists with {$permissionCount} records");
            
            if ($permissionCount > 0) {
                $permissions = DB::table('permissions')->select('name', 'display_name', 'is_restricted')->get();
                $this->table(
                    ['Name', 'Display Name', 'Restricted'],
                    $permissions->map(function ($p) {
                        return [$p->name, $p->display_name, $p->is_restricted ? 'Yes' : 'No'];
                    })->toArray()
                );
            }
        } else {
            $this->error("❌ Permissions table missing");
        }

        // Check user_permissions table
        $this->info("\n=== Checking User Permissions Table ===");
        if (Schema::hasTable('user_permissions')) {
            $userPermissionCount = DB::table('user_permissions')->count();
            $this->info("✅ User permissions table exists with {$userPermissionCount} records");
        } else {
            $this->error("❌ User permissions table missing");
        }

        // Check for existing sub-users
        $this->info("\n=== Checking Existing Sub-Users ===");
        $subUsers = DB::table('users')->where('is_sub_user', true)->get();
        if ($subUsers->count() > 0) {
            $this->info("Found {$subUsers->count()} sub-users:");
            $this->table(
                ['ID', 'Name', 'Email', 'Parent ID', 'Sub Role'],
                $subUsers->map(function ($u) {
                    return [$u->id, $u->name, $u->email, $u->parent_user_id, $u->sub_user_role];
                })->toArray()
            );
        } else {
            $this->info("No sub-users found in database");
        }

        return 0;
    }
}