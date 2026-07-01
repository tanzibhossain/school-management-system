<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        if (! File::isDirectory($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $modulePath) {
            $this->loadModuleMigrations($modulePath);
            $this->loadModuleRoutes($modulePath);
        }
    }

    private function loadModuleMigrations(string $modulePath): void
    {
        $migrationsPath = $modulePath . '/database/migrations';

        if (File::isDirectory($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    private function loadModuleRoutes(string $modulePath): void
    {
        $apiRoutes = $modulePath . '/routes/api.php';
        if (File::exists($apiRoutes)) {
            Route::middleware('api')
                ->prefix('api')
                ->group($apiRoutes);
        }

        $webRoutes = $modulePath . '/routes/web.php';
        if (File::exists($webRoutes)) {
            Route::middleware('web')
                ->group($webRoutes);
        }
    }
}
