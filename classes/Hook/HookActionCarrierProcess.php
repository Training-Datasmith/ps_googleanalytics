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

use Context;
use Presta_Shop\Module\Ps_Googleanalytics\Repository\Carrier_Repository;
use Ps_Googleanalytics;
class Hook_Action_Carrier_Process implements Hook_Interface
{
    private $module;
    private $context;
    private $params;
    public function __construct(Ps_Googleanalytics $module, Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }
    /**
     * run
     */
    public function run(): void
    {
        if (isset($this->params['cart']->id_carrier)) {
            $carrier_repository = new Carrier_Repository();
            // Load carrier name
            $carrier_name = (string) $carrier_repository->find_by_carrier_id((int) $this->params['cart']->id_carrier);
            // Check if we actually have some name
            if (empty($carrier_name)) {
                return;
            }
            // Prepare and render the event
            $event_data = ['currency' => $this->context->currency->iso_code, 'value' => (float) $this->context->cart->get_summary_details()['total_price'], 'shipping_tier' => $carrier_name];
            $js_code = $this->module->get_tools()->render_event('add_shipping_info', $event_data);
            // Store it into our repository so we can flush it on next page load
            $this->module->get_data_handler()->persist_data($js_code);
        }
    }
    /**
     * @param array $params
     */
    public function set_params($params): void
    {
        $this->params = $params;
    }
}