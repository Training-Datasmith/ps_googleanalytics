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
namespace Presta_Shop\Module\Ps_Googleanalytics\Handler;

use Module;
class Module_Handler
{
    /**
     * @param string $moduleName
     */
    public function is_module_enabled($module_name): bool
    {
        $module = Module::get_instance_by_name($module_name);
        if (!$module instanceof Module) {
            return false;
        }
        if (false === Module::is_installed($module_name)) {
            return false;
        }
        if (false === $module->active) {
            return false;
        }
        return true;
    }
    /**
     * @param string $moduleName
     * @param string $hookName
     *
     * @return bool
     */
    public function is_module_enabled_and_hooked_on($module_name, $hook_name)
    {
        $module = Module::get_instance_by_name($module_name);
        if (false === $this->is_module_enabled($module_name)) {
            return false;
        }
        return $module->is_registered_in_hook($hook_name);
    }
    /**
     * @param string $moduleName
     *
     * @return bool
     */
    public function uninstall_module($module_name)
    {
        if (false === Module::is_installed($module_name)) {
            return false;
        }
        $old_module = Module::get_instance_by_name($module_name);
        if (!$old_module instanceof Module) {
            return false;
        }
        if (method_exists($old_module, 'uninstallTab')) {
            $old_module->uninstall_tab();
        }
        // This closure calls the parent class to prevent data to be erased
        $parent_uninstall_closure = function () {
            return parent::uninstall();
        };
        $parent_uninstall_closure = $parent_uninstall_closure->bind_to($old_module, get_class($old_module));
        return $parent_uninstall_closure();
    }
}