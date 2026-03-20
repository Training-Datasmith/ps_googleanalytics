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
namespace Presta_Shop\Module\Ps_Googleanalytics\Hooks;

use Configuration;
use Context;
use Customer;
use Ps_Googleanalytics;
use Tools;
class Hook_Display_Header implements Hook_Interface
{
    /**
     * @var Ps_Googleanalytics
     */
    private $module;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var bool
     */
    private $back_office;
    public function __construct(Ps_Googleanalytics $module, Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }
    /**
     * @return false|string
     */
    public function run()
    {
        if (!Configuration::get('GA_ACCOUNT_ID')) {
            return '';
        }
        // Resolve if we should add user ID into the code
        $user_id = null;
        if (Configuration::get('GA_USERID_ENABLED') && $this->context->customer instanceof Customer && $this->context->customer->is_logged()) {
            $user_id = (int) $this->context->customer->id;
        }
        $this->context->smarty->assign(['backOffice' => $this->back_office, 'trackBackOffice' => Configuration::get('GA_TRACK_BACKOFFICE_ENABLED'), 'userId' => $user_id, 'gaAccountId' => Tools::safe_output(Configuration::get('GA_ACCOUNT_ID')), 'gaAnonymizeEnabled' => Configuration::get('GA_ANONYMIZE_ENABLED')]);
        return $this->module->display($this->module->get_local_path() . $this->module->name, 'ps_googleanalytics.tpl');
    }
    /**
     * @param bool $backOffice
     */
    public function set_back_office($back_office): void
    {
        $this->back_office = $back_office;
    }
}