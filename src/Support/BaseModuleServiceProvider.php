<?php

namespace Newnet\Module\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Newnet\AdminUi\Facades\AdminMenu;
use Symfony\Component\Finder\Finder;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    public function getModuleNamespace()
    {
        $refClass = new \ReflectionClass($this);
        if ($refClass->hasConstant('MODULE_NAMESPACE')){
            return $refClass->getConstant('MODULE_NAMESPACE');
        } else {
            return Str::lower(Str::studly(Str::replace('ServiceProvider', '', $refClass->getShortName())));
        }
    }

    public function register()
    {
        $this->registerHelpers();

        $configFile = $this->getModuleNamespace().'.php';
        $configFilePath = $this->getModuleFilePath("config/{$configFile}");
        if (File::exists($configFilePath)) {
            $this->mergeConfigFrom($configFilePath, 'cms.'.$this->getModuleNamespace());
        }

        $this->loadJsonTranslationsFrom($this->getModuleFilePath('lang'));
    }

    public function boot()
    {
        $this->loadMigrations();

        $this->loadTranslationsFrom($this->getModuleFilePath('lang'), $this->getModuleNamespace());
        $this->loadJsonTranslationsFrom($this->getModuleFilePath('lang'));
        $this->loadViewsFrom($this->getModuleFilePath('resources/views'), $this->getModuleNamespace());

        $configFile = $this->getModuleNamespace().'.php';
        $configFilePath = $this->getModuleFilePath("config/{$configFile}");
        if (File::exists($configFilePath)) {
            $this->publishes([
                $configFilePath => config_path('cms/'.$configFile),
            ], 'module-config');
        }

        if (is_dir($this->getModuleDir().'/public')) {
            $this->publishes([
                $this->getModuleDir().'/public' => public_path('vendor/'.$this->getModuleNamespace()),
            ], 'module-assets');
        }

        $this->loadRoutes();
        $this->registerPermissions();
        $this->registerAdminMenus();
        $this->registerFrontendMenuBuilders();
        $this->registerDashboards();
        $this->loadCommands();
    }

    protected function loadRoutes()
    {
        $routeAdmin = $this->getModuleDir().'/routes/admin.php';
        if (file_exists($routeAdmin)) {
            Route::middleware(config('core.admin_middleware'))
                ->domain(config('core.admin_domain'))
                ->prefix(config('core.admin_prefix'))
                ->group($routeAdmin);
        }

        $routeWeb = $this->getModuleDir().'/routes/web.php';
        if (file_exists($routeWeb)) {
            Route::middleware(['web'])
                ->group($routeWeb);
        }

        $routeApi = $this->getModuleDir().'/routes/api.php';
        if (file_exists($routeApi)) {
            Route::middleware(['api'])
                ->prefix('api')
                ->group($routeApi);
        }
    }

    protected function registerPermissions()
    {
        // Code
    }

    protected function registerAdminMenus()
    {
        $adminMenuFile = $this->getModuleFilePath('routes/menus.php');
        AdminMenu::loadMenuFrom($adminMenuFile);
    }

    protected function registerFrontendMenuBuilders()
    {

    }

    protected function registerDashboards()
    {

    }

    protected function loadCommands()
    {

    }

    protected function getModuleDir()
    {
        $class_info = new \ReflectionClass($this);
        return dirname(dirname($class_info->getFileName()));
    }

    protected function getModuleFilePath($path)
    {
        return $this->getModuleDir().DIRECTORY_SEPARATOR.$path;
    }

    protected function registerHelpers()
    {
        $paths = $this->getModuleDir().'/helpers';

        $paths = array_unique(Arr::wrap($paths));

        $paths = array_filter($paths, function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        foreach (Finder::create()->in($paths)->files() as $file) {
            require_once $file;
        }
    }

    protected function loadMigrations()
    {
        $this->loadMigrationsFrom($this->getModuleFilePath('database/migrations'));
    }
}
