<?php

namespace AHAbid\LaravelRefreshModuleDatabase;

use AHAbid\LaravelRefreshModuleDatabase\States\DropAllTablesState;
use AHAbid\LaravelRefreshModuleDatabase\States\MigrationSchemaCreateState;
use AHAbid\LaravelRefreshModuleDatabase\States\RefreshModulesDatabaseState;
use AHAbid\LaravelRefreshModuleDatabase\States\RefreshRootDatabaseState;
use App\Console\Kernel;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait RefreshModuleDatabase
{
    use RefreshDatabase;

    /**
     * Run Module Migration
     *
     * @param string $moduleName
     * @return void
     */
    abstract protected function runModuleMigration($moduleName);

    /**
     * Should migrate all database files, including root & modules.
     *
     * Can be overriden
     *
     * @var bool
     */
    protected function shouldMigrateAllFiles()
    {
        return env('TDD_MIGRATE_ALL_FILES', false);
    }

    /**
     * Should migrate root database files. Not module ones.
     *
     * Can be overriden
     *
     * @var bool
     */
    protected function shouldMigrateRootFiles()
    {
        return false;
    }

    /**
     * List of modules to migrate
     *
     * Can be overriden
     *
     * @var array
     */
    protected function modulesToMigrate()
    {
        return [];
    }

    /**
     * Get Console Kernel Class
     *
     * @return string
     */
    protected function getConsoleKernelClass()
    {
        return Kernel::class;
    }

    /**
     * Set Console Artisan to Null
     *
     * @return void
     */
    protected function clearConsoleArtisan()
    {
        $this->app[$this->getConsoleKernelClass()]->setArtisan(null);
    }

    /**
     * Refresh a conventional test database.
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        if (!RefreshDatabaseState::$migrated) {
            $this->dropAllDatabaseTables();

            $this->createEmptyMigrationSchema();

            $this->runMigration();
        }

        $this->beginDatabaseTransaction();

        try {
            if (!$this->app->make('db.transactions')->getTransactions()->isEmpty()) {
                DB::commit();
            }
        } catch (BindingResolutionException $e) {}
    }

    /**
     * Drop All Database Tables
     *
     * @return void
     */
    protected function dropAllDatabaseTables()
    {
        if (DropAllTablesState::$dropped) {
            return;
        }

        $this->artisan('db:wipe', [
            '--force' => true,
        ]);

        $this->clearConsoleArtisan();

        DropAllTablesState::$dropped = true;
    }

    /**
     * Create the migrations table in empty state
     *
     * @return void
     */
    protected function createEmptyMigrationSchema()
    {
        if (MigrationSchemaCreateState::$created) {
            return;
        }

        $migrationTable = config('database.migrations');
        if (Schema::hasTable($migrationTable)) {
            DB::statement('TRUNCATE ' . $migrationTable);
        } else {
            $this->artisan('migrate:install');

            $this->clearConsoleArtisan();
        }

        MigrationSchemaCreateState::$created = true;
    }

    /**
     * Run migration
     *
     * @return void
     */
    protected function runMigration()
    {
        if ($this->shouldMigrateAllFiles()) {
            return $this->migrateAllFilesToDatabase();
        }

        $this->migrateOnlyRootFilesToDatabase();

        $this->migrateModuleFilesToDatabase();
    }

    /**
     * Migrate all files (including Modules) to database
     *
     * @return void
     */
    protected function migrateAllFilesToDatabase()
    {
        $this->artisan('migrate');

        $this->clearConsoleArtisan();

        RefreshDatabaseState::$migrated = true;
    }

    /**
     * Migrate only root files (in project-root/database/migrations) to database
     *
     * @return void
     */
    protected function migrateOnlyRootFilesToDatabase()
    {
        if (
            !$this->shouldMigrateRootFiles()
            || RefreshRootDatabaseState::$migrated
        ) {
            return;
        }

        $this->artisan('migrate', [
            '--path' => 'database/migrations',
        ]);

        $this->clearConsoleArtisan();

        RefreshRootDatabaseState::$migrated = true;
    }

    /**
     * Migrate module migration files to database
     *
     * @return void
     */
    protected function migrateModuleFilesToDatabase()
    {
        foreach ($this->modulesToMigrate() as $moduleName) {
            if (!array_key_exists($moduleName, RefreshModulesDatabaseState::$modulesMigrated)) {
                RefreshModulesDatabaseState::$modulesMigrated[$moduleName] = false;
            }

            if (!RefreshModulesDatabaseState::$modulesMigrated[$moduleName]) {
                $this->runModuleMigration($moduleName);

                $this->clearConsoleArtisan();

                RefreshModulesDatabaseState::$modulesMigrated[$moduleName] = true;
            }
        }
    }
}
