<?php

namespace AHAbid\LaravelRefreshModuleDatabase\States;

class RefreshModulesDatabaseState
{
    /**
     * Indicates if the modules migration files has been migrated.
     *
     * Array Format: ModuleName => true/false,
     *
     * @var array
     */
    public static $modulesMigrated = [];
}
