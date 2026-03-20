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

use Cart;
use Configuration;
use Context;
use Presta_Shop\Module\Ps_Googleanalytics\Handler\Ganalytics_Js_Handler;
use Presta_Shop\Module\Ps_Googleanalytics\Repository\Ganalytics_Repository;
use Presta_Shop\Module\Ps_Googleanalytics\Wrapper\Order_Wrapper;
use Presta_Shop\Module\Ps_Googleanalytics\Wrapper\Product_Wrapper;
use Ps_Googleanalytics;
use Validate;
class Hook_Display_Order_Confirmation implements Hook_Interface
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
     *
     * @return string
     */
    public function run()
    {
        $ga_scripts = '';
        $order = $this->params['order'];
        if (!Validate::is_loaded_object($order) || $order->get_current_state() == (int) Configuration::get('PS_OS_ERROR')) {
            return $ga_scripts;
        }
        // Load up our handlers and repositories
        $ganalytics_repository = new Ganalytics_Repository();
        $ga_tag_handler = new Ganalytics_Js_Handler($this->module, $this->context);
        $product_wrapper = new Product_Wrapper($this->context);
        $order_wrapper = new Order_Wrapper($this->context);
        // If it's a completely new order, add order to repository, so we can later mark it as sent
        if (empty($ganalytics_repository->find_ga_order_by_order_id((int) $order->id))) {
            $ganalytics_repository->add_order((int) $order->id, (int) $order->id_shop);
        }
        // If the customer is revisiting confirmation screen and the order was already sent, we don't do anything
        if ($ganalytics_repository->has_order_been_already_sent((int) $order->id)) {
            return $ga_scripts;
        }
        // Prepare transaction data
        $order_data = $order_wrapper->wrap_order($order);
        // Prepare order products, if the cart still exists
        $order_products = [];
        $cart = new Cart($order->id_cart);
        if (Validate::is_loaded_object($cart)) {
            $order_products = $product_wrapper->prepare_item_list_from_product_list($cart->get_products(), true);
        }
        // Add payment event
        $ga_scripts .= $this->module->get_tools()->render_event('add_payment_info', ['currency' => $order_data['currency'], 'value' => (float) $order_data['value'], 'payment_type' => $order_data['payment_type'], 'items' => $order_products]);
        // Render transaction code
        $ga_scripts .= $this->module->get_tools()->render_purchase_event($order_products, $order_data, $this->context->link->get_module_link('ps_googleanalytics', 'ajax', [], true));
        return $ga_tag_handler->generate($ga_scripts);
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
}