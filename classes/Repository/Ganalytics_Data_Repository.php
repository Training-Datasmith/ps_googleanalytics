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
namespace Presta_Shop\Module\Ps_Googleanalytics\Repository;

use Db;
class Ganalytics_Data_Repository
{
    public const TABLE_NAME = 'ganalytics_data';
    /**
     * findByCartId
     *
     * @param int $cartId
     * @param int $shopId
     *
     * @return mixed
     */
    public function find_data_by_cart_id_and_shop_id($cart_id, $shop_id)
    {
        return Db::get_instance()->get_value('SELECT data
            FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`
            WHERE id_cart = ' . (int) $cart_id . '
                AND id_shop = ' . (int) $shop_id);
    }
    /**
     * addNewRow
     *
     * @param int $cartId
     * @param int $shopId
     * @param string $data
     *
     * @return bool
     */
    public function add_new_row($cart_id, $shop_id, $data)
    {
        return Db::get_instance()->Execute('INSERT INTO `' . _DB_PREFIX_ . self::TABLE_NAME . '` (id_cart, id_shop, data)
            VALUES(\'' . (int) $cart_id . '\',\'' . (int) $shop_id . '\',\'' . p_sql($data) . '\')
            ON DUPLICATE KEY UPDATE data = \'' . p_sql($data) . '\';');
    }
    /**
     * deleteRow
     *
     * @param int $cartId
     * @param int $shopId
     *
     * @return bool
     */
    public function delete_row($cart_id, $shop_id)
    {
        return Db::get_instance()->delete(self::TABLE_NAME, 'id_cart = ' . (int) $cart_id . ' AND id_shop = ' . (int) $shop_id);
    }
}