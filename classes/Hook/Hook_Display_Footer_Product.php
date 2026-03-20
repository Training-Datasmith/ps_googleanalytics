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
class Hook_Display_Footer_Product implements Hook_Interface
{
    private $module;
    private $context;
    public function __construct(Ps_Googleanalytics $module, Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }
    /**
     * run
     *
     * @return string|void
     */
    public function run()
    {
        // Check we are really on product page
        if ($this->context->controller->php_self !== 'product') {
            return;
        }
        // Get lazy array from context
        $product = $this->context->smarty->get_template_vars('product');
        if (empty($product)) {
            return;
        }
        // Initialize tag handler
        $ga_tag_handler = new Ganalytics_Js_Handler($this->module, $this->context);
        // Prepare it and format it for our purpose
        $product_wrapper = new Product_Wrapper($this->context);
        $item = $product_wrapper->prepare_item_from_product($product);
        $js_code = '';
        // Prepare and render event
        $event_data = ['currency' => $this->context->currency->iso_code, 'value' => $item['price'], 'items' => [$item]];
        $js_code .= $this->module->get_tools()->render_event('view_item', $event_data);
        // If the user got to the product page from previous page on our shop,
        // we will also send select_item event.
        if ($this->was_previous_page_our_shop()) {
            $event_data = ['items' => [$item]];
            // We will also try to get the information about the last visited listing.
            // We save this information into a cookie. If it's the page that got the user here,
            // we will use it.
            $previous_listing_data = $this->get_last_visited_listing();
            if (!empty($previous_listing_data)) {
                $event_data = array_merge($previous_listing_data, $event_data);
            }
            // Render the event
            $js_code .= $this->module->get_tools()->render_event('select_item', $event_data);
        }
        return $ga_tag_handler->generate($js_code);
    }
    /**
     * Checks HTTP_REFERER to see if the previous page that got user to this product
     * was our shop.
     */
    private function was_previous_page_our_shop(): bool
    {
        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
            return true;
        }
        return false;
    }
    /**
     * Tries to get details of previous listing from the cookie.
     *
     * @return bool|array
     */
    private function get_last_visited_listing()
    {
        // Fetch it from the cookie
        $last_listing = $this->context->cookie->ga_last_listing;
        if (empty($last_listing)) {
            return false;
        }
        // Decode the data and check if it contains something sensible
        $last_listing = json_decode($last_listing, true);
        if (empty($last_listing['item_list_id'])) {
            return false;
        }
        // Check if the last listing is the page the user came from
        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $last_listing['item_list_url']) !== false) {
            unset($last_listing['item_list_url']);
            return $last_listing;
        }
        return false;
    }
}