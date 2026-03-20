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
use Db;
use Order;
use Presta_Shop\Module\Ps_Googleanalytics\Handler\Ganalytics_Js_Handler;
use Presta_Shop\Module\Ps_Googleanalytics\Repository\Ganalytics_Repository;
use Presta_Shop\Module\Ps_Googleanalytics\Wrapper\Order_Wrapper;
use Presta_Shop\Module\Ps_Googleanalytics\Wrapper\Product_Wrapper;
use Ps_Googleanalytics;
use Tools;
use Validate;
class Hook_Display_Back_Office_Header implements Hook_Interface
{
    private $module;
    private $context;
    private $ga_scripts = '';
    public function __construct(Ps_Googleanalytics $module, Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }
    /**
     * run
     */
    public function run(): string
    {
        // Add assets if we are on configuration page
        if (strcmp(Tools::get_value('configure'), $this->module->name) === 0) {
            $this->context->controller->add_css($this->module->get_path_uri() . 'views/css/ganalytics.css');
        }
        // Render base tag using displayHeader hook with backoffice parameter
        $this->ga_scripts .= $this->module->hook_display_header(null, true);
        // Process manual orders instantly, we have their IDs in cookie
        $this->process_manual_orders();
        // Backload old orders that failed to load normally
        $this->process_failed_orders();
        return $this->ga_scripts;
    }
    /**
     * Checks if there are any orders that failed to be sent normally through front office and processes them
     */
    protected function process_failed_orders()
    {
        if (empty(Configuration::get('GA_BACKLOAD_ENABLED'))) {
            return;
        }
        // Check for value on how long back we will get them
        $backload_days = (int) Configuration::get('GA_BACKLOAD_DAYS');
        if ($backload_days < 1) {
            return;
        }
        // Get all failed orders (either not present in our table or not sent)
        // We go GA_BACKLOAD_DAYS into the past and at least 30 minutes old
        $failed_orders = Db::get_instance()->execute_s('SELECT DISTINCT o.id_order, g.sent FROM `' . _DB_PREFIX_ . 'orders` o
            LEFT JOIN `' . _DB_PREFIX_ . Ganalytics_Repository::TABLE_NAME . '` g ON o.id_order = g.id_order
            WHERE (g.sent IS NULL OR g.sent = 0) AND o.date_add BETWEEN NOW() - INTERVAL ' . $backload_days . ' DAY AND NOW() - INTERVAL 30 MINUTE');
        // Process each failed order
        foreach ($failed_orders as $row) {
            $this->process_order((int) $row['id_order']);
        }
    }
    /**
     * Checks if there are any manual orders in cookie and processes them
     */
    protected function process_manual_orders()
    {
        $admin_orders = $this->context->cookie->ga_admin_order;
        if (empty($admin_orders)) {
            return;
        }
        // Separate them by IDs and process one by one
        $admin_orders = explode(',', $admin_orders);
        foreach ($admin_orders as $id_order) {
            $this->process_order((int) $id_order);
        }
        // Clean up the cookie
        unset($this->context->cookie->ga_admin_order);
        $this->context->cookie->write();
    }
    /**
     * Renders tracking code for given order
     *
     * @param int $idOrder
     */
    public function process_order($id_order): void
    {
        $order = new Order((int) $id_order);
        if (!Validate::is_loaded_object($order) || $order->get_current_state() == (int) Configuration::get('PS_OS_ERROR')) {
            return;
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
        // If the order was already sent for some reason, don't do anything
        if ($ganalytics_repository->has_order_been_already_sent((int) $order->id)) {
            return;
        }
        // Prepare transaction data
        $order_data = $order_wrapper->wrap_order($order);
        // Empty script for the current order
        $ga_scripts = '';
        // Prepare order products, if the cart still exists
        $order_products = [];
        $cart = new Cart($order->id_cart);
        if (Validate::is_loaded_object($cart)) {
            $order_products = $product_wrapper->prepare_item_list_from_product_list($cart->get_products(), true);
        }
        // Add payment event
        $ga_scripts .= $this->module->get_tools()->render_event('add_payment_info', ['currency' => $order_data['currency'], 'value' => (float) $order_data['value'], 'payment_type' => $order_data['payment_type'], 'items' => $order_products]);
        // Render transaction code
        $ga_scripts .= $this->module->get_tools()->render_purchase_event($order_products, $order_data, $this->context->link->get_admin_link('AdminGanalyticsAjax'));
        $this->ga_scripts .= $ga_tag_handler->generate($ga_scripts);
    }
}