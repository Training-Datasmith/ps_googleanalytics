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
use Presta_Shop\Module\Ps_Googleanalytics\Wrapper\Product_Wrapper;
use Product;
use Ps_Googleanalytics;
use Validate;
class Hook_Action_Object_Product_In_Cart_Delete_Before implements Hook_Interface
{
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
        // Format product and standardize ID
        $product = new Product((int) $this->params['id_product'], false, (int) $this->context->language->id);
        if (!Validate::is_loaded_object($product)) {
            return;
        }
        $product = (array) $product;
        $product['id_product'] = $product['id'];
        // Get some basic information
        $product = Product::get_product_properties($this->context->language->id, $product);
        // Add information about attribute
        if (!empty($this->params['id_product_attribute'])) {
            $product['id_product_attribute'] = (int) $this->params['id_product_attribute'];
        }
        // Prepare it and format it for our purpose
        $product_wrapper = new Product_Wrapper($this->context);
        $item = $product_wrapper->prepare_item_from_product($product, false);
        // Prepare and render event
        $event_data = ['currency' => $this->context->currency->iso_code, 'value' => $item['price'] * $item['quantity'], 'items' => [$item]];
        $js_code = $this->module->get_tools()->render_event('remove_from_cart', $event_data);
        // Store this event
        $this->module->get_data_handler()->persist_data($js_code);
    }
    /**
     * @param array $params
     */
    public function set_params($params): void
    {
        $this->params = $params;
    }
}