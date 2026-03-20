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
use Db;
use Ps_Googleanalytics;
class Hook_Action_Order_Status_Post_Update implements Hook_Interface
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
     * @var array
     */
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
        // If we do not have an order or a new order status, we return
        if (empty($this->params['id_order']) || empty($this->params['newOrderStatus']->id)) {
            return;
        }
        // We get all states in which the merchant want to have refund sent and check if the new state being set belongs there
        $ga_cancelled_states = json_decode(Configuration::get('GA_CANCELLED_STATES'), true);
        if (empty($ga_cancelled_states) || !in_array($this->params['newOrderStatus']->id, $ga_cancelled_states)) {
            return;
        }
        // We check if the refund was already sent to Google Analytics
        $ga_refund_sent = Db::get_instance()->get_value('SELECT id_order FROM `' . _DB_PREFIX_ . 'ganalytics` WHERE id_order = ' . (int) $this->params['id_order'] . ' AND refund_sent = 1');
        // If it was not already refunded
        if ($ga_refund_sent === false) {
            // We refund it and set the "sent" flag to true
            $js_code = $this->get_google_analytics4($this->params['id_order']);
            $this->context->cookie->ga_admin_refund = $js_code;
            $this->context->cookie->write();
            // We save this information to database
            Db::get_instance()->execute('UPDATE `' . _DB_PREFIX_ . 'ganalytics` SET refund_sent = 1 WHERE id_order = ' . (int) $this->params['id_order']);
        }
    }
    /**
     * setParams
     *
     * @param array $params
     */
    public function set_params($params): void
    {
        $this->params = $params;
    }
    /**
     * @param int $idOrder
     */
    protected function get_google_analytics4($id_order)
    {
        $event_data = ['transaction_id' => (int) $id_order];
        return $this->module->get_tools()->render_event('refund', $event_data);
    }
}