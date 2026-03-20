<?php

declare (strict_types=1);
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
namespace Presta_Shop\Module\Ps_Googleanalytics\Database;

use Db;
use Tab;
use Validate;
class Uninstall
{
    /**
     * uninstallTables
     */
    public function uninstall_tables(): bool
    {
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ganalytics`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ganalytics_data`';
        foreach ($sql as $query) {
            if (!Db::get_instance()->execute($query)) {
                return false;
            }
        }
        return true;
    }
    /**
     * uninstall tab
     *
     * @return bool
     */
    public function uninstall_tab()
    {
        $result = true;
        $id_tab = (int) Tab::get_id_from_class_name('AdminGanalyticsAjax');
        $tab = new Tab($id_tab);
        if (Validate::is_loaded_object($tab)) {
            return $tab->delete();
        }
        return $result;
    }
}