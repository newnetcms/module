<?php

namespace Newnet\Module;

use Composer\Autoload\ClassLoader;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Newnet\Module\Console\Commands\ModuleCreateCommand;

class ModuleServiceProvider extends ServiceProvider
{
    protected array $providers = [];

    public function boot()
    {
        $this->registerModules();
        $this->registerProviders();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleCreateCommand::class
            ]);
        }
    }

    protected function registerModules(): void
    {
        $modules = ModuleLoader::getEnabledModules();

        $composerLoader = new ClassLoader();
        foreach ($modules as $key => $enable) {
            if ($enable) {
                $moduleDefineFile = $this->getModuleFilePath($key, 'composer.json');

                if (!File::exists($moduleDefineFile)) {
                    continue;
                }

                $moduleDefineContent = json_decode(File::get($moduleDefineFile), true);

                if (isset($moduleDefineContent['autoload']['psr-4'])) {
                    foreach ($moduleDefineContent['autoload']['psr-4'] as $namespace => $src) {
                        $srcPath = $this->getModuleFilePath($key, $src);
                        $composerLoader->setPsr4($namespace, $srcPath);
                    }
                }

                if (isset($moduleDefineContent['extra']['laravel']['providers'])) {
                    $this->providers = array_merge($this->providers, $moduleDefineContent['extra']['laravel']['providers']);
                }
            }
        }

        $composerLoader->register();
    }

    protected function registerProviders(): void
    {
        foreach ($this->providers as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    protected function getModuleDir($moduleKey): string
    {
        return base_path('modules').DIRECTORY_SEPARATOR.$moduleKey;
    }

    protected function getModuleFilePath($moduleKey, $filePath): string
    {
        return $this->getModuleDir($moduleKey).DIRECTORY_SEPARATOR.$filePath;
    }
}
