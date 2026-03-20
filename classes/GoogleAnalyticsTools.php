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
namespace Presta_Shop\Module\Ps_Googleanalytics;

use Configuration;
class Google_Analytics_Tools
{
    /**
     * Renders purchase event for order
     *
     * @param array $orderProducts
     *
     * @return string|void
     */
    public function render_purchase_event($order_products, array $order_data, string $callback_url)
    {
        if (!is_array($order_products)) {
            return;
        }
        $callback_data = ['orderid' => $order_data['transaction_id'], 'customer' => $order_data['customer']];
        $event_data = ['transaction_id' => (int) $order_data['transaction_id'], 'affiliation' => $order_data['affiliation'], 'value' => (float) $order_data['value'], 'tax' => (float) $order_data['tax'], 'shipping' => (float) $order_data['shipping'], 'currency' => $order_data['currency'], 'items' => $order_products, 'event_callback' => "function() {\n                \$.get('" . $callback_url . "', " . json_encode($callback_data, JSON_UNESCAPED_UNICODE) . ');
            }'];
        return $this->render_event('purchase', $event_data, ['event_callback']);
    }
    /**
     * Encodes array of data into JSON, optionally ignoring some of the values
     *
     * @param array $data Data pairs
     * @param array $ignoredKeys Values of these keys won't be encoded, for literal output of functions
     *
     * @return string json encoded data
     */
    public function json_encode_with_blacklist($data, $ignored_keys = []): string
    {
        $return = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $ignored_keys)) {
                $return[] = json_encode($k, JSON_UNESCAPED_UNICODE) . ': ' . $v;
            } else {
                $return[] = json_encode($k, JSON_UNESCAPED_UNICODE) . ': ' . json_encode($v, JSON_UNESCAPED_UNICODE);
            }
        }
        return '{' . implode(', ', $return) . '}';
    }
    /**
     * Renders gtag event and encodes the data. You can optionally pass which data keys you want to
     * output in a raw way - callbacks for example.
     *
     * @param string $eventName
     * @param array $eventData
     * @param array $ignoredKeys Values of these keys won't be encoded, for literal output of functions
     *
     * @return string render gtag event for output
     */
    public function render_event($event_name, $event_data, $ignored_keys = []): string
    {
        // Automatically add send_to parameter to all events to avoid sending extra events
        // to other gtag configs (Ads for example).
        $event_data = array_merge(['send_to' => Configuration::get('GA_ACCOUNT_ID')], $event_data);
        return sprintf('gtag("event", "%1$s", %2$s);', $event_name, $this->json_encode_with_blacklist($event_data, $ignored_keys));
    }
}