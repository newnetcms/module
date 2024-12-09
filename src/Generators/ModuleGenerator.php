<?php

namespace Newnet\Module\Generators;

use Illuminate\Console\Command as Console;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleGenerator
{
    protected string $moduleName;

    protected bool $isDev;

    protected array $models;

    protected Console $console;

    protected array $folders = [
        'config',
        'public',
        'routes',
        'database/migrations',
        'resources/views',
        'lang/en',
        'lang/vi',
        'src/Http/Controllers/Admin',
        'src/Http/Controllers/Web',
        'src/Http/Middleware',
        'src/Http/Requests',
        'src/Models',
        'src/Services',
        'src/Console/Commands',
        'src/Providers',
    ];

    protected array $files = [
        'composer-json.stub'   => 'composer.json',
        'gitignore.stub'       => '.gitignore',
        'routes/web.stub'      => 'routes/web.php',
        'routes/api.stub'      => 'routes/api.php',
        'config/config.stub'   => 'config/__MODULE_NAMESPACE__.php',
        'module-provider.stub' => 'src/__MODULE_CLASS_NAME__ServiceProvider.php',
        'lang/module-en.stub'  => 'lang/en/module.php',
        'lang/module-vi.stub'  => 'lang/vi/module.php',
    ];

    protected array $modelFiles = [
        'controllers/admin.stub'       => 'src/Http/Controllers/Admin/__MODEL_CLASS_NAME__Controller.php',
        'controllers/web.stub'         => 'src/Http/Controllers/Web/__MODEL_CLASS_NAME__Controller.php',
        'requests/request.stub'        => 'src/Http/Requests/__MODEL_CLASS_NAME__Request.php',
        'repositories/repository.stub' => 'src/Repositories/__MODEL_CLASS_NAME__Repository.php',
        'views/admin/index.stub'       => 'resources/views/admin/__MODEL_SLUG_NAME__/index.blade.php',
        'views/admin/edit.stub'        => 'resources/views/admin/__MODEL_SLUG_NAME__/edit.blade.php',
        'views/admin/create.stub'      => 'resources/views/admin/__MODEL_SLUG_NAME__/create.blade.php',
        'views/web/content.stub'       => 'resources/views/web/__MODEL_SLUG_NAME__/content.blade.php',
        'views/web/detail.stub'        => 'resources/views/web/__MODEL_SLUG_NAME__/detail.blade.php',
        'lang/message-en.stub'         => 'lang/en/__MODEL_SLUG_NAME__.php',
        'lang/message-vi.stub'         => 'lang/vi/__MODEL_SLUG_NAME__.php',
    ];

    protected bool $enableSeo = true;

    protected bool $enableMultipleLanguage = true;

    protected string $designPattern;

    public function setModuleName($moduleName): static
    {
        $this->moduleName = $moduleName;

        return $this;
    }

    public function setConsole(Console $console): static
    {
        $this->console = $console;

        return $this;
    }

    public function setModelsList(array $models): static
    {
        $this->models = $models;

        return $this;
    }

    public function setDesignPattern(string $designPattern): static
    {
        $this->designPattern = $designPattern;

        return $this;
    }

    public function setEnableSeo(string $enableSeo): static
    {
        $this->enableSeo = $enableSeo;

        return $this;
    }

    public function setEnableMultipleLanguage(string $enableMultipleLanguage): static
    {
        $this->enableMultipleLanguage = $enableMultipleLanguage;

        return $this;
    }

    public function setIsDev($isDev): static
    {
        $this->isDev = $isDev;

        return $this;
    }

    public function generate(): void
    {
        if (File::isDirectory($this->getModulePath())) {
            $this->console->error(sprintf('Module %s exists', $this->moduleName));
            return;
        }

        $this->generateFolders();
        $this->generateFiles();
        $this->generateModelFiles();
        $this->generateMenuRoutes();
        $this->generateAdminMenuKey();
        $this->generateAdminRoutes();
        $this->generateWebRoutes();
        $this->generateMigration();
    }

    public function makeModel(): void
    {
        if (!File::isDirectory($this->getModulePath())) {
            $this->console->error(sprintf('Module %s not exists', $this->moduleName));
            return;
        }

        $this->generateModelFiles();
        $this->generateMigration();
    }

    protected function generateFolders(): void
    {
        foreach ($this->folders as $folder) {
            $path = $this->getModulePath().DIRECTORY_SEPARATOR.$folder;

            File::makeDirectory($path, 0755, true);
        }
    }

    protected function generateFiles(): void
    {
        foreach ($this->files as $stub => $path) {
            $path = $this->getModulePath().DIRECTORY_SEPARATOR.$this->replacement($path);

            if (!File::isDirectory($dir = dirname($path))) {
                File::makeDirectory($dir, 0775, true);
            }

            File::put($path, $this->getStubContent($stub));
        }
    }

    protected function generateModelFiles(): void
    {
        $modelFiles = $this->modelFiles;

        if ($this->enableSeo) {
            $modelFiles['model_seo.stub'] = 'src/Models/__MODEL_CLASS_NAME__.php';
            $modelFiles['views/admin/_fields_seo.stub'] = 'resources/views/admin/__MODEL_SLUG_NAME__/_fields.blade.php';
        } else {
            $modelFiles['model.stub'] = 'src/Models/__MODEL_CLASS_NAME__.php';
            $modelFiles['views/admin/_fields.stub'] = 'resources/views/admin/__MODEL_SLUG_NAME__/_fields.blade.php';
        }

        foreach ($this->models as $model) {
            foreach ($modelFiles as $stub => $path) {
                $path = $this->getModulePath().DIRECTORY_SEPARATOR.$this->replacementModel($path, $model);

                if (!File::isDirectory($dir = dirname($path))) {
                    File::makeDirectory($dir, 0775, true);
                }

                File::put($path, $this->getStubContentModel($stub, $model));
            }
        }
    }

    protected function generateMenuRoutes(): void
    {
        $adminMenus = [];

        foreach ($this->models as $key => $model) {
            $order = $key + 1;

            $adminMenuContent = "
AdminMenu::addItem(__('__MODULE_NAMESPACE__::__MODEL_SLUG_NAME__.model_name'), [
    'id'     => __MODULE_CLASS_NAME__AdminMenuKey::__MODEL_MENU_KEY__,
    'parent' => __MODULE_CLASS_NAME__AdminMenuKey::__MODULE_MENU_KEY__,
    'route'  => '__MODULE_NAMESPACE__.admin.__MODEL_SLUG_NAME__.index',
    'icon'   => 'fas fa-cube',
    'order'  => {$order},
]);
            ";

            $adminMenuContent = $this->replacementModel($adminMenuContent, $model);

            $adminMenus[] = $adminMenuContent;
        }

        $path = $this->getModulePath().DIRECTORY_SEPARATOR.$this->replacement('routes/menus.php');
        $content = $this->getStubContent('routes/menus.stub');
        $content = str_replace([
            '// ADD_ADMIN_MENU_HERE //',
        ], [
            implode("", $adminMenus)."\n".'// ADD_ADMIN_MENU_HERE //',
        ], $content);

        File::put($path, $content);
    }

    protected function generateAdminMenuKey(): void
    {
        $adminMenus = [];

        foreach ($this->models as $model) {
            $adminMenuKeyContent = "const __MODEL_MENU_KEY__ = '__MODULE_KEY_____MODEL_KEY__';";
            $adminMenuKeyContent = $this->replacementModel($adminMenuKeyContent, $model);
            $adminMenus[] = $adminMenuKeyContent;
        }

        $path = $this->getModulePath().DIRECTORY_SEPARATOR.$this->replacement('src/__MODULE_CLASS_NAME__AdminMenuKey.php');
        $content = $this->getStubContent('admin-menu-key.stub');
        $content = str_replace([
            '// ADD_ADMIN_MENU_KEY_HERE //',
        ], [
            implode("\t", $adminMenus)."\n\t".'// ADD_ADMIN_MENU_KEY_HERE //',
        ], $content);

        File::put($path, $content);
    }

    protected function generateAdminRoutes(): void
    {
        $routes = [];

        foreach ($this->models as $model) {
            $routeContent = "Route::resource('__MODEL_SLUG_NAME__', \Modules\__MODULE_CLASS_NAME__\Http\Controllers\Admin\__MODEL_CLASS_NAME__Controller::class);";

            $routeContent = $this->replacementModel($routeContent, $model);

            $routes[] = $routeContent;
        }

        $path = $this->getModulePath().DIRECTORY_SEPARATOR.$this->replacement('routes/admin.php');
        $content = $this->getStubContent('routes/admin.stub');
        $content = str_replace([
            '// ADD_ROUTE_MODEL_HERE //',
        ], [
            implode("\n\t\t", $routes)."\n\t\t".'// ADD_ROUTE_MODEL_HERE //',
        ], $content);

        File::put($path, $content);
    }

    protected function generateWebRoutes(): void
    {
        $routesUse = [];
        $routes = [];

        foreach ($this->models as $model) {
            $routeUseContent = "use Modules\__MODULE_CLASS_NAME__\Http\Controllers\Web\__MODEL_CLASS_NAME__Controller;";
            $routeContent = "Route::get('__MODULE_NAMESPACE__/__MODEL_SLUG_NAME__/{id}', [__MODEL_CLASS_NAME__Controller::class, 'detail'])->name('__MODULE_NAMESPACE__.web.__MODEL_SLUG_NAME__.detail');";

            $routeUseContent = $this->replacementModel($routeUseContent, $model);
            $routeContent = $this->replacementModel($routeContent, $model);

            $routesUse[] = $routeUseContent;
            $routes[] = $routeContent;
        }

        $path = $this->getModulePath().DIRECTORY_SEPARATOR.$this->replacement('routes/web.php');
        $content = $this->getStubContent('routes/web.stub');
        $content = str_replace([
            '// ADD_WEB_USE_ROUTE_MODEL_HERE //',
            '// ADD_WEB_ROUTE_MODEL_HERE //',
        ], [
            implode("\n", $routesUse)."\n".'// ADD_WEB_USE_ROUTE_MODEL_HERE //',
            implode("\n", $routes)."\n".'// ADD_WEB_ROUTE_MODEL_HERE //',
        ], $content);

        File::put($path, $content);
    }

    protected function generateMigration(): void
    {
        foreach ($this->models as $model) {
            $prefix = date('Y_m_d_His');
            $stub = 'migration.stub';
            $path = "database/migrations/{$prefix}_create___MODEL_TABLE___table.php";

            $path = $this->getModulePath().DIRECTORY_SEPARATOR.$this->replacementModel($path, $model);

            $content = $this->getStubContent($stub);
            $content = $this->replacementModel($content, $model);

            File::put($path, $content);
        }
    }

    protected function replacement($content): string
    {
        return str_replace([
            '__MODULE_NAME__',          // Module Name
            '__MODULE_CLASS_NAME__',    // ModuleName
            '__MODULE_FOLDER__',        // module-name
            '__MODULE_SLUG_NAME__',     // module-name
            '__MODULE_NAMESPACE__',     // modulename
            '__MODULE_KEY__',           // module_name
            '__MODULE_MENU_KEY__',      // MODULE_NAME
        ], [
            $this->moduleName,
            $this->getModuleClassName(),
            $this->getModuleFolder(),
            $this->getModuleSlugName(),
            $this->getModuleNamespace(),
            $this->getModuleKey(),
            $this->getModuleMenuKey(),
        ], $content);
    }

    protected function replacementModel($content, $modelName): string
    {
        $content = $this->replacement($content);

        return str_replace([
            '__MODEL_NAME__',           // Model Name
            '__MODEL_CLASS_NAME__',     // ModelName
            '__MODEL_SLUG_NAME__',      // model-name
            '__MODEL_VAR_NAME__',       // modelName
            '__MODEL_KEY__',            // model_name
            '__MODEL_MENU_KEY__',       // MODEL_NAME
            '__MODEL_TABLE__',          // module__my_models
        ], [
            $modelName,
            $this->getModelClassName($modelName),
            $this->getModelSlugName($modelName),
            $this->getModelVarName($modelName),
            $this->getModelKey($modelName),
            $this->getModelMenuKey($modelName),
            $this->getModuleNamespace().'__'.Str::plural($this->getModelKey($modelName)),
        ], $content);
    }

    protected function getModulePath(): string
    {
        if ($this->isDev) {
            return base_path("lib".DIRECTORY_SEPARATOR.$this->getModuleFolder());
        } else {
            return base_path("modules".DIRECTORY_SEPARATOR.$this->getModuleFolder());
        }
    }

    protected function getModuleFolder(): string
    {
        return Str::kebab($this->moduleName);
    }

    protected function getModuleClassName(): string
    {
        return Str::studly($this->moduleName);
    }

    protected function getModuleSlugName(): string
    {
        return Str::kebab($this->moduleName);
    }

    protected function getModuleNamespace(): string
    {
        return Str::lower(Str::studly($this->moduleName));
    }

    protected function getModuleKey(): string
    {
        return Str::snake($this->moduleName);
    }

    protected function getModuleMenuKey(): string
    {
        return Str::upper(Str::snake($this->moduleName));
    }

    protected function getModelClassName($modelName): string
    {
        return Str::studly($modelName);
    }

    protected function getModelSlugName($modelName): string
    {
        return Str::kebab($modelName);
    }

    private function getModelVarName($modelName): string
    {
        return Str::camel($modelName);
    }

    private function getModelKey($modelName): string
    {
        return Str::snake($modelName, '_');
    }

    private function getModelMenuKey($modelName): string
    {
        return Str::upper(Str::snake($modelName, '_'));
    }

    protected function getStubContent($stub): string
    {
        $content = File::get(__DIR__.'/../../stubs/'.$stub);

        return $this->replacement($content);
    }

    protected function getStubContentModel($stub, $modelName): string
    {
        $content = File::get(__DIR__.'/../../stubs/'.$stub);

        $content = $this->replacement($content);

        return $this->replacementModel($content, $modelName);
    }
}
