<?php

namespace Newnet\Module;

class ModuleLoader
{
    protected static $modules;

    public static function getEnabledModules()
    {
        if (!self::$modules) {
            self::$modules = array_merge(self::loadModuleListFromRoot(), self::loadModuleListFromStorage());
        }

        return self::$modules;
    }

    public static function loadModuleListFromRoot()
    {
        $rootModuleJson = base_path('modules.json');

        $modules = [];
        if (file_exists($rootModuleJson)) {
            $modules = json_decode(file_get_contents($rootModuleJson), true);
        }

        return $modules;
    }

    public static function loadModuleListFromStorage()
    {
        $appModuleJson = storage_path('modules.json');

        $modules = [];
        if (file_exists($appModuleJson)) {
            $modules = json_decode(file_get_contents($appModuleJson), true);
        }

        return $modules;
    }

    public static function saveModuleListStorage($moduleList)
    {
        file_put_contents(storage_path('modules.json'), json_encode($moduleList, JSON_PRETTY_PRINT));
    }
}
