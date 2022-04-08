# Laravel Refresh Module Database

A Laravel database trait that can used in unit test for module packages like nwidart/laravel-modules, pingpong/modules, caffeinated/modules etc.


## What it solves

TLDR; **Speeds up Unit Tests** by migrating only the required modules.


### Long Story

I have a big laravel project where I'm using the popular `nwidart/laravel-modules` package for modularizing the codebase. There are about 20 modules and each of then have about 10-15 migration files. When I run `php artisan migrate:fresh` command, it takes 15-20 seconds just to complete all the migration. Now you can understand how much it frustrates when I have to unit test a small section, I have to wait 15-20 seconds just to complete migration then the unit test takes 100-200ms.

So I worked on this refresh database trait where I can choose to only migrate by specific modules. This really up the small unit tests by 90%.


## Requirement

* Laravel 6.0 or above
* Database: I only tested with MySQL. I think Postgres or SQLite should work fine.

*Note*: This will not work for using in-memory database.


## Install

Install using composer

```
composer require a-h-abid/laravel-refresh-module-database
```

## Usage

Import the Trait to your test code. Best to import it on your base test class.

```php
class ExampleTest extends TestCase
{
    use \AHAbid\LaravelRefreshModuleDatabase\RefreshMooduleDatabase;
}
```

Next, add the below code in your test class for running module migration. Below example is for `nwidart/laravel-modules` package. If you are using any other package or any other mechanism, change the code inside as you need.

```php
protected function runModuleMigration($moduleName)
{
    $this->artisan('module:migrate ' . $moduleName);
}
```

Finally add this code in your test class, which indicates the modules to migrate.

```php
protected function modulesToMigrate()
{
    return ['ModuleA', 'ModuleB'];
}
```

Now, run your phpunit and see the result. :)


## How It Works

Similar to Laravel's Refresh Database trait, on initiation, it will drop all tables and then migrate the migration files once. Only difference here is that we can choose to only migrate specific modules in test class. Also once a module is migrated, it will not re-migrate / re-fresh on that test session.


## Other Usage Options

1. If you also need to run the migration files in `project-root/database/migrations` directory, add below method and set to `true`.

```php
protected function shouldMigrateRootFiles()
{
    return true;
}
```

2. If you need to migrate all files, set this env in your `phpunit.xml` file with value to `true`.

```xml
  <env name="TDD_MIGRATE_ALL_FILES" value="true" />
```

## TODO

* Improve documentation.
* Implement TDD.


## License

This project is licensed under the terms of the MIT license.
