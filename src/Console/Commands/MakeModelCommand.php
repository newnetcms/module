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

class MakeModelCommand extends Command
{
    protected $signature = 'cms:module.make-model {module_name?} {models?} {--dev}';

    protected $description = 'Create a new model in module';

    public function handle()
    {
        $name = $this->askForModuleName();
        $models = $this->askForModelsList();
        $isDev = $this->option('dev');

        $modelList = implode(',', $models);

        $this->line("<options=bold>Module Name:</options=bold> {$name}");
        $this->line("<options=bold>Models:</options=bold> {$modelList}");

        app(ModuleGenerator::class)
            ->setModuleName($name)
            ->setModelsList($models)
            ->setConsole($this)
            ->setIsDev($isDev)
            ->makeModel();
    }

    protected function askForModuleName()
    {
        if ($name = $this->argument('module_name')) {
            return $name;
        }

        do {
            $name = $this->ask('Enter Module Name');
            if ($name == '') {
                $this->error('Module Name is required');
            } else {
                $moduleFolder = Str::kebab($name);
                if (!File::isDirectory(base_path("modules/{$moduleFolder}"))) {
                    $this->error(sprintf('Module "%s" not exists', $name));
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
            $name = $this->ask('Enter Model Name');
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
