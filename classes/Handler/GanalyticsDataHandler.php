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
namespace Presta_Shop\Module\Ps_Googleanalytics\Handler;

use Presta_Shop\Module\Ps_Googleanalytics\Repository\Ganalytics_Data_Repository;
class Ganalytics_Data_Handler
{
    private $ganalytics_data_repository;
    private $cart_id;
    private $shop_id;
    /**
     * __construct
     *
     * @param int $cartId
     * @param int $shopId
     */
    public function __construct($cart_id, $shop_id)
    {
        $this->ganalytics_data_repository = new Ganalytics_Data_Repository();
        $this->cart_id = (int) $cart_id;
        $this->shop_id = (int) $shop_id;
    }
    /**
     * readData
     *
     * @return array
     */
    public function read_data()
    {
        $data_returned = $this->ganalytics_data_repository->find_data_by_cart_id_and_shop_id($this->cart_id, $this->shop_id);
        if (false === $data_returned) {
            return [];
        }
        return $this->json_decode_valid_json($data_returned);
    }
    /**
     * Deletes all persisted data, probably because it was flushed.
     *
     * @return bool
     */
    public function delete_data()
    {
        return $this->ganalytics_data_repository->delete_row($this->cart_id, $this->shop_id);
    }
    /**
     * Stores event into data repository so we can output it
     * on first available chance.
     *
     * @param string $dataToPersist
     *
     * @return bool
     */
    public function persist_data($data_to_persist)
    {
        // Try to get current data
        $current_data = $this->read_data();
        // If no data has been persisted yet, we create a new array, otherwise
        // we add it to the previous events stored.
        if (!empty($current_data)) {
            $new_data = $current_data;
            $new_data[] = $data_to_persist;
        } else {
            $new_data = [$data_to_persist];
        }
        return $this->ganalytics_data_repository->add_new_row((int) $this->cart_id, (int) $this->shop_id, json_encode($new_data));
    }
    /**
     * Check if the json is valid and returns an empty array if not
     *
     * @param string $json
     *
     * @return array
     */
    protected function json_decode_valid_json($json)
    {
        $array = json_decode($json, true);
        if (JSON_ERROR_NONE === json_last_error()) {
            return $array;
        }
        return [];
    }
}