<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Module $module
 * @return Module
 */
function upgrade_module_1_0_6($module)
{
    $table_name = _DB_PREFIX_ . bqSQL($module->name);

    $look_for_columns = Db::getInstance()->ExecuteS(sprintf('SHOW COLUMNS FROM `%s` LIKE \'one_shot\'', $table_name));

    if (false === $look_for_columns) {
        Db::getInstance()->Execute(sprintf(
            'ALTER TABLE `%s` ADD `one_shot` BOOLEAN NOT NULL DEFAULT 0 AFTER `updated_at`',
            $table_name
        ));
    }

    return $module;
}
