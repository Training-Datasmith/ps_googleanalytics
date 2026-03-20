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
use Order_Detail;
use Ps_Googleanalytics;
use Validate;
class Hook_Action_Product_Cancel implements Hook_Interface
{
    /**
     * @var Ps_Googleanalytics
     */
    private $module;
    /**
     * @var Context
     */
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
        if (!isset($this->params['id_order_detail']) || !isset($this->params['cancel_quantity'])) {
            return;
        }
        // Display GA refund product
        $order_detail = new Order_Detail($this->params['id_order_detail']);
        // Check if the hook provided us with a valid existing ID of order detail.
        // An example are automatic tests, which do not provide it unfortunately.
        if (!Validate::is_loaded_object($order_detail)) {
            return;
        }
        $id_product = empty($order_detail->product_attribute_id) ? $order_detail->product_id : $order_detail->product_id . '-' . $order_detail->product_attribute_id;
        $js_code = $this->get_google_analytics4((int) $this->params['order']->id, $id_product, (float) $this->params['cancel_quantity'], $order_detail->product_name);
        $this->context->cookie->ga_admin_refund = $js_code;
        $this->context->cookie->write();
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
     * @param string $idProduct
     * @param float $quantity
     * @param string $nameProduct
     */
    protected function get_google_analytics4($id_order, $id_product, $quantity, $name_product)
    {
        $event_data = ['transaction_id' => (int) $id_order, 'items' => [['item_id' => (int) $id_product, 'item_name' => $name_product, 'quantity' => (int) $quantity]]];
        return $this->module->get_tools()->render_event('refund', $event_data);
    }
}