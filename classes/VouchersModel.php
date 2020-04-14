<?php
/**
* 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2020 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

class VouchersModel extends ObjectModel
{
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'fop_vouchers',
        'primary' => 'id_fop_vouchers',
        'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_cart_rule' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
        ),
    );
    public $id_customer;
    public $id_order;
    public $id_product;
    public $id_cart_rule;

    public static function getCartRulesIds($id_customer, $id_order, $id_product = null)
    {
        $sql = 'SELECT * 
                    FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' 
                    WHERE id_customer = ' . (int)$id_customer . ' 
                    AND id_order = ' . (int)$id_order;
        if ($id_product) {
            $sql = ' AND id_product = ' . (int)$id_product;
        }
        $cart_rules = array();
        if ($list_id = Db::getInstance()->executeS($sql)) {
            foreach ($list_id as $row) {
                $cart_rules[$row['id_fop_vouchers']] = new CartRule((int)$row['id_cart_rule']);
            }
        }
        return $cart_rules;
    }


    public static function exportCodes()
    {
        $sql = 'SELECT c.firstname, c.lastname, c.email, cr.code, cr.date_add, cr.date_to, 
        cr.reduction_amount, currl.symbol as currency
                FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' fopv
                LEFT JOIN ' . _DB_PREFIX_ . 'customer c 
                    ON (c.id_customer = fopv.id_customer)
                LEFT JOIN ' . _DB_PREFIX_ . 'orders o 
                    ON (o.id_order = fopv.id_order)
                LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule cr 
                    ON (cr.id_cart_rule = fopv.id_cart_rule)
                LEFT JOIN ' . _DB_PREFIX_ . 'currency curr 
                    ON (curr.id_currency = o.id_currency)
                LEFT JOIN ' . _DB_PREFIX_ . 'currency_lang currl 
                    ON (currl.id_currency = curr.id_currency AND currl.id_lang = o.id_lang)';
        return Db::getInstance()->executeS($sql);
    }
}
