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
class AdminCronJobsController extends ModuleAdminController
{
    public function __construct()
    {
        if (Tools::getValue('token') != Configuration::getGlobalValue('CRONJOBS_EXECUTION_TOKEN')) {
            exit('Invalid token');
        }

        parent::__construct();

        $this->postProcess();

        exit;
    }

    /**
     * @return bool|ObjectModel|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $this->module->sendCallback();

        ob_start();

        $this->runModulesCrons();
        $this->runTasksCrons();

        ob_end_clean();
    }

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function runModulesCrons()
    {
        $query = 'SELECT * FROM ' . _DB_PREFIX_ . bqSQL($this->module->name) . ' WHERE `active` = 1 AND `id_module` IS NOT NULL';
        $crons = Db::getInstance()->executeS($query);
        $table_name = _DB_PREFIX_ . bqSQL($this->module->name);

        if (is_array($crons) && (count($crons) > 0)) {
            foreach ($crons as &$cron) {
                $module = Module::getInstanceById((int) $cron['id_module']);

                $delete_query = sprintf(
                    "DELETE FROM `%s` WHERE `id_cronjob` = '%s'",
                    $table_name,
                    (int) $cron['id_cronjob']
                );

                if ($module == false) {
                    Db::getInstance()->execute($delete_query);
                    break;
                } elseif ($this->shouldBeExecuted($cron) == true) {
                    Hook::exec('actionCronJob', [], $cron['id_module']);
                    $query =
                    Db::getInstance()->execute($query);
                }
            }
        }
    }

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     */
    protected function runTasksCrons()
    {
        $query = 'SELECT * FROM ' . _DB_PREFIX_ . bqSQL($this->module->name) . ' WHERE `active` = 1 AND `id_module` IS NULL';
        $crons = Db::getInstance()->executeS($query);

        if (is_array($crons) && (count($crons) > 0)) {
            foreach ($crons as &$cron) {
                if ($this->shouldBeExecuted($cron) == true) {
                    Tools::file_get_contents(urldecode($cron['task']), false);
                    $query = sprintf(
                        "UPDATE `%s` SET `updated_at` = NOW(), `active` = IF (`one_shot` = TRUE, FALSE, `active`) WHERE `id_cronjob` = '%s'",
                        _DB_PREFIX_ . bqSQL($this->module->name),
                        (int) $cron['id_cronjob']
                    );

                    Db::getInstance()->execute($query);
                }
            }
        }
    }

    /**
     * @param array $cron
     * @return bool
     */
    protected function shouldBeExecuted($cron)
    {
        $hour = ($cron['hour'] == -1) ? date('H') : $cron['hour'];
        $day = ($cron['day'] == -1) ? date('d') : $cron['day'];
        $month = ($cron['month'] == -1) ? date('m') : $cron['month'];
        $day_of_week = ($cron['day_of_week'] == -1) ? date('D') : date('D', strtotime('Sunday +' . $cron['day_of_week'] . ' days'));

        $day = date('Y') . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
        $execution = $day_of_week . ' ' . $day . ' ' . str_pad($hour, 2, '0', STR_PAD_LEFT);
        $now = date('D Y-m-d H');

        return !(bool) strcmp($now, $execution);
    }
}
