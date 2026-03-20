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
use Presta_Shop\Module\Ps_Googleanalytics\Handler\Ganalytics_Js_Handler;
use Presta_Shop\Module\Ps_Googleanalytics\Wrapper\Product_Wrapper;
use Ps_Googleanalytics;
class Hook_Display_Before_Body_Closing_Tag implements Hook_Interface
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
     *
     * @return string
     */
    public function run()
    {
        // Prepare our tag handler
        $ga_tag_handler = new Ganalytics_Js_Handler($this->module, $this->context);
        // Log information about product listing
        $this->save_information_about_listing();
        // Flush events stored in data storage from previous pages
        $this->output_stored_events();
        // Add events
        $this->render_product_listing();
        $this->render_search();
        $this->render_cart_page();
        $this->render_begin_checkout();
        $this->render_login();
        $this->render_registration();
        // Output everything
        return $ga_tag_handler->generate($this->ga_scripts);
    }
    /**
     * This method renders tracking code for product listings, like category pages.
     */
    private function render_product_listing(): void
    {
        // Try to get product list variable
        $listing = $this->context->smarty->get_template_vars('listing');
        if (empty($listing['products'])) {
            return;
        }
        // Prepare items to our format
        $product_wrapper = new Product_Wrapper($this->context);
        $items = $product_wrapper->prepare_item_list_from_product_list($listing['products']);
        // Prepare info about the list
        $item_list_id = $this->context->controller->php_self;
        $item_list_name = $listing['label'];
        // Render the event
        $event_data = ['item_list_id' => $item_list_id, 'item_list_name' => $item_list_name, 'items' => $items];
        $this->ga_scripts .= $this->module->get_tools()->render_event('view_item_list', $event_data);
        // Render quickview events
        foreach ($items as $item) {
            $event_data = ['item_list_id' => $item_list_id, 'item_list_name' => $item_list_name, 'items' => [$item]];
            // Keep only product ID if id_product_attribute was appended
            $product_id = explode('-', $item['item_id']);
            $product_id = $product_id[0];
            // Render the event wrapped in onclick
            $this->ga_scripts .= '
            $(\'article[data-id-product="' . $product_id . '"] a.quick-view\').on(
                "click",
                function() {' . $this->module->get_tools()->render_event('select_item', $event_data) . '}
            );
            ';
        }
    }
    /**
     * This method renders tracking code when user searches on the shop.
     */
    private function render_search(): void
    {
        // Check if we are on search page and we have a search string
        if ($this->context->controller->php_self != 'search' || empty($_GET['s'])) {
            return;
        }
        // Render the event
        $event_data = ['search_term' => (string) $_GET['s']];
        $this->ga_scripts .= $this->module->get_tools()->render_event('search', $event_data);
    }
    /**
     * This method renders tracking code for product listings, like category pages.
     */
    private function render_cartpage(): void
    {
        // Check if we are on cart page
        if ($this->context->controller->php_self != 'cart') {
            return;
        }
        // Try to get product list variable and check if it's not empty
        $cart = $this->context->smarty->get_template_vars('cart');
        if (empty($cart['products'])) {
            return;
        }
        // Prepare items to our format
        $product_wrapper = new Product_Wrapper($this->context);
        $items = $product_wrapper->prepare_item_list_from_product_list($cart['products'], true);
        // Render the event
        $event_data = ['currency' => $this->context->currency->iso_code, 'value' => $cart['totals']['total']['amount'], 'items' => $items];
        $this->ga_scripts .= $this->module->get_tools()->render_event('view_cart', $event_data);
    }
    /**
     * This method renders tracking code for product listings, like category pages.
     */
    private function render_begin_checkout(): void
    {
        // Check if we are on some supported order controller
        $allowed_controllers = ['order', 'orderopc', 'checkout'];
        if (!in_array($this->context->controller->php_self, $allowed_controllers)) {
            return;
        }
        // If the user reliably came from previous page, we won't render this event
        // We want to do it just for first visiting checkout
        if (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['REQUEST_URI']) !== false) {
            return;
        }
        // Try to get product list variable and check if it's not empty
        $cart = $this->context->smarty->get_template_vars('cart');
        if (empty($cart['products'])) {
            return;
        }
        // Prepare items to our format
        $product_wrapper = new Product_Wrapper($this->context);
        $items = $product_wrapper->prepare_item_list_from_product_list($cart['products'], true);
        // Render the event
        $event_data = ['currency' => $this->context->currency->iso_code, 'value' => $cart['totals']['total']['amount'], 'items' => $items];
        $this->ga_scripts .= $this->module->get_tools()->render_event('begin_checkout', $event_data);
    }
    /**
     * This method renders tracking code after user logs in.
     */
    private function render_login(): void
    {
        // Render it only on login page AND if we are not creating a new account in older PS versions
        // For newer versions, registrations are handled with standalone registration controller.
        if ($this->context->controller->php_self != 'authentication' || isset($_GET['create_account'])) {
            return;
        }
        // Render the event
        $this->ga_scripts .= $this->module->get_tools()->render_event('login', []);
    }
    /**
     * This method renders tracking code after user registers.
     */
    private function render_registration(): void
    {
        if ($this->context->controller->php_self != 'registration' && ($this->context->controller->php_self != 'authentication' || !isset($_GET['create_account']))) {
            return;
        }
        // Render the event
        $this->ga_scripts .= $this->module->get_tools()->render_event('sign_up', []);
    }
    /**
     * Saves information about last visited product listing, so we can later use it for select_item event.
     */
    private function save_information_about_listing(): void
    {
        // Try to get product list variable
        $listing = $this->context->smarty->get_template_vars('listing');
        if (empty($listing['products']) || empty($listing['label'])) {
            return;
        }
        // Save this information to a cookie
        $this->context->cookie->ga_last_listing = json_encode(['item_list_url' => $_SERVER['REQUEST_URI'], 'item_list_id' => $this->context->controller->php_self, 'item_list_name' => $listing['label']]);
    }
    /**
     * Outputs all events we stored into data repository during previous AJAX requests
     * on previous page.
     */
    private function output_stored_events(): void
    {
        // Get all stored events
        $stored_events = $this->module->get_data_handler()->read_data();
        if (empty($stored_events)) {
            return;
        }
        foreach ($stored_events as $event) {
            $this->ga_scripts .= $event;
        }
        // Delete the repository because everything has been flushed
        $this->module->get_data_handler()->delete_data();
    }
}