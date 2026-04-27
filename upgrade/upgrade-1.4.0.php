<?php

function upgrade_module_1_4_0($module)
{
    return (Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'product_file` 
        ADD `nb_download` INT(10) NULL DEFAULT 0')
    ) && $module->registerHook("displayAdminStatsModules");
}
