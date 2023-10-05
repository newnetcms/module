<?php

namespace Newnet\Module\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Newnet\Module\Enums\DesignPattern;
use Newnet\Module\Enums\EnableMultipleLanguage;
use Newnet\Module\Enums\EnableSeo;
use Newnet\Module\Generators\ModuleGenerator;
use function Laravel\Prompts\text;

class ModuleCreateCommand extends Command
{
    protected $signature = 'cms:module.create {name?} {models?} {--dev}';

    protected $description = 'Create a new Module';

    public function handle()
    {
        $name = $this->askForName();
        $models = $this->askForModelsList();
        $designPattern = $this->askForDesignPattern();
        $enableSeo = $this->askForEnableSeo();
        $enableMultipleLanguage = $this->askForEnableMultipleLanguage();
        $isDev = $this->option('dev');

        $modelList = implode(',', $models);

        $this->line("<options=bold>Module Name:</options=bold> {$name}");
        $this->line("<options=bold>Models:</options=bold> {$modelList}");
        $this->line("<options=bold>Design Pattern:</options=bold> {$designPattern}");
        $this->line("<options=bold>Enable SEO:</options=bold> {$enableSeo}");
        $this->line("<options=bold>Multiple Language:</options=bold> {$enableMultipleLanguage}");
        $this->line('');

        $this->info("Module <options=bold>{$name}</options=bold> successfully created.\n");

        app(ModuleGenerator::class)
            ->setModuleName($name)
            ->setModelsList($models)
            ->setConsole($this)
            ->setDesignPattern($designPattern)
            ->setEnableSeo($enableSeo == EnableSeo::YES)
            ->setEnableMultipleLanguage($enableMultipleLanguage == EnableMultipleLanguage::YES)
            ->setIsDev($isDev)
            ->generate();
    }

    protected function askForName()
    {
//        $isDev = $this->option('dev');
//        $dir = $isDev ? "lib" : "modules";
//
//        return text(
//            label: 'Enter Module Name?',
//            required: true,
//            validate: fn (string $val) => match (true) {
//                File::isDirectory(base_path($dir.DIRECTORY_SEPARATOR.Str::kebab($val))) => sprintf('Module "%s" already exists', $val),
//                default => null
//            }
//        );

        if ($name = $this->argument('name')) {
            return $name;
        }

        do {
            $name = $this->ask('Enter Module Name');
            if ($name == '') {
                $this->error('Module Name is required');
            } else {
                $moduleFolder = Str::kebab($name);
                if (File::isDirectory(base_path("modules/{$moduleFolder}"))) {
                    $this->error(sprintf('Module "%s" already exists', $name));
                    $name = '';
                }
            }
        } while (!$name);

        return $name;
    }

    protected function askForModelsList()
    {
        if ($models = $this->argument('models')) {
            return explode(',', $models);
        }

        $models = [];

        do {
            $name = $this->ask('Enter Model Name (Leave blank to skip)');
            if ($name) {
                $name = Str::studly($name);
                if (in_array($name, $models)) {
                    $this->error(sprintf('Model "%s" already exists', $name));
                } else {
                    $models[] = $name;
                }
            }
        } while ($name);

        return $models;
    }

    protected function askForDesignPattern()
    {
        $options = [
            DesignPattern::CONTROLLER_MODEL,
            DesignPattern::CONTROLLER_RESPOSITORY_MODEL,
            DesignPattern::CONTROLLER_SERVICE_RESPOSITORY_MODEL,
        ];

        return $this->choice(
            'Select Module Pattern?',
            $options,
            $_default = $options[1],
            $_maxAttempts = null,
            $_allowMultipleSelections = false
        );
    }

    protected function askForEnableSeo()
    {
        $options = [
            EnableSeo::NO,
            EnableSeo::YES,
        ];

        return $this->choice(
            'Enable SEO?',
            $options,
            $_default = $options[1],
            $_maxAttempts = null,
            $_allowMultipleSelections = false
        );
    }

    protected function askForEnableMultipleLanguage()
    {
        $options = [
            EnableMultipleLanguage::NO,
            EnableMultipleLanguage::YES,
        ];

        return $this->choice(
            'Enable Multiple Language?',
            $options,
            $_default = $options[1],
            $_maxAttempts = null,
            $_allowMultipleSelections = false
        );
    }
}
