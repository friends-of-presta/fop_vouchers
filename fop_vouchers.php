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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once dirname(__FILE__) . '/classes/VouchersModel.php';

class Fop_vouchers extends Module
{
    public function __construct()
    {
        $this->name = 'fop_vouchers';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'FOP - StoreCommander';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('FOP - vouchers');
        $this->description = $this->l('Allow your customer to buy vouchers');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install()
            && $this->installDB()
            && $this->registerHook('displayOrderDetail')
            && $this->registerHook('actionOrderStatusUpdate');
    }

    public function installDB()
    {
        $return = true;
        $return &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'fop_vouchers` (
                `id_fop_vouchers` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_customer` int(10) unsigned NOT NULL ,
                `id_order` int(10) unsigned NOT NULL ,
                `id_product` int(10) unsigned NOT NULL ,
                `id_cart_rule` int(10) unsigned NOT NULL ,
                PRIMARY KEY (`id_fop_vouchers`),
                KEY `id_customer` (`id_customer`),
                KEY `id_order` (`id_order`),
                KEY `id_product` (`id_product`),
                KEY `id_cart_rule` (`id_cart_rule`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;');

        return $return;
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('FOP_VOUCHERS_DATA')
            && $this->uninstallDB();
    }

    public function uninstallDB()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'fop_vouchers`');
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';

        /**
         * If values have been submitted in the form, process.
         */
        if ((bool)Tools::isSubmit('submitFop_vouchersExport')) {
            $this->postProcessExportCsv();
        } elseif (((bool)Tools::isSubmit('submitFop_vouchersModule')) == true) {
            $output = $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitFop_vouchersModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $intro = $this->l('Start by creating different products that correspond to your vouchers:');
        $intro .= '<br/>'.$this->l('"$5 gift certificate"');
        $intro .= '<br/>'.$this->l('"$10 gift certificate"');
        $intro .= '<br/>'.$this->l('Etc.');
        $intro .= '<br/><br/>'.$this->l('Then fill in the product IDs here :');

        $middle = $this->l('When your customers order these products, they will receive an email with the discount codes to be used on the ecommerce site.');
        $middle .= '<br/>'.$this->l('They will also be accessible in their order history.');
        $middle .= '<br/><br/>'.$this->l('After ordering, these vouchers are valid for :');

        $outro = $this->l('To use them on the site, the customer must enter his discount code on his shopping cart.');
        $outro_link = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name.'&submitFop_vouchersExport=1&token='.Tools::getAdminTokenLite('AdminModules');
        $outro_button = '<strong><a href="'.$outro_link.'">'.$this->l('click here').' <i class="process-icon-export" style="display: contents;font-size: 16px;"></i></a></strong>';
        $outro .= '<br/>'.sprintf($this->l('You can export the vouchers by %s in order to make a comparison with the codes indicated by your customers in the physical store.'), $outro_button);
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'html',
                        'label' => '',
                        'name' => '<div class="alert alert-info">'.$intro.'</div>',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('List of products associated with vouchers'),
                        'name' => 'id_product_list',
                        'desc' => $this->l('id_product separated by ;'),
                        'required' => true
                    ),
                    array(
                        'type' => 'html',
                        'label' => '',
                        'name' => '<div class="alert alert-info">'.$middle.'</div>',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Voucher duration'),
                        'name' => 'voucher_duration',
                        'desc' => implode('<br>', array(
                            $this->l('y: year'),
                            $this->l('m: month'),
                            $this->l('w: week'),
                            $this->l('d: day'),
                            $this->l('h: hour'),
                            $this->l('example : 3;m = 3 months'),
                            $this->l('example : 1;w = 1 week')
                        )),
                        'required' => true
                    ),
                    array(
                        'type' => 'html',
                        'label' => '',
                        'name' => '<div class="alert alert-info">'.$outro.'</div>',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
                'buttons' => array(
                    'export-all-codes' => array(
                        'title' => $this->l('Export all codes'),
                        'name' => 'submitFop_vouchersExport',
                        'type' => 'submit',
                        'class' => 'btn btn-default pull-right',
                        'icon' => 'process-icon-export',
                    ),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $fop_vouchers_data = json_decode(Configuration::get('FOP_VOUCHERS_DATA'), true);
        return array(
            'id_product_list' => $fop_vouchers_data['id_product_list'],
            'voucher_duration' => $fop_vouchers_data['voucher_duration'],
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $errors = array();
        $fop_vouchers_data = array(
            'id_product_list' => pSQL(Tools::getValue('id_product_list')),
            'voucher_duration' => pSQL(Tools::getValue('voucher_duration')),
        );

        if (preg_match('/([0-9]*;)/', $fop_vouchers_data['id_product_list']) !== 1) {
            $errors[] = $this->l('Product list ID:wrong format => id_product;id_product...');
        }
        if (preg_match('/^[0-9]*;[ymwdhYMWDH]$/', $fop_vouchers_data['voucher_duration']) !== 1) {
            $errors[] = $this->l('Voucher duration:wrong format => number;y or number;h...');
        }

        if (empty($errors)) {
            Configuration::updateValue('FOP_VOUCHERS_DATA', json_encode($fop_vouchers_data));
        } else {
            return $this->displayError(implode('<br/>', $errors));
        }
    }

    protected function postProcessExportCsv()
    {
        $codes = VouchersModel::exportCodes();
        if (!empty($codes)) {
            ## download file
            $delimiter = ';';
            $enclosure = '"';
            $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF));
            $export_file_name = 'Fop_vouchers_expor_' . date('Y-m-d_H-i-s') . '.csv';
            header("Content-disposition: attachment; filename=$export_file_name");
            header("Content-Type: text/csv");

            $fp = fopen("php://output", 'w');

            fputs($fp, $bom);
            fputcsv($fp, array_keys($codes[0]), $delimiter, $enclosure);

            foreach ($codes as $fields) {
                fputcsv($fp, $fields, $delimiter, $enclosure);
            }

            fclose($fp);
            exit;
        }
    }

    public function hookDisplayOrderDetail($params)
    {
        $cart_rules = VouchersModel::getCartRulesIds($this->context->customer->id, $params['order']->id);
        $currency = $this->context->currency;
        $vouchers = array();
        foreach ($cart_rules as $cart_rule) {
            $vouchers[$cart_rule->id] = $cart_rule;
            $vouchers[$cart_rule->id]->reduction_amount = Tools::displayPrice($cart_rule->reduction_amount, $currency);
        }
        $this->context->smarty->assign([
            'vouchers' => $vouchers
        ]);

        return $this->display(__FILE__, 'order_detail.tpl');
    }

    public function hookActionOrderStatusUpdate($params)
    {
        $action = null;

        ## do something depending orderStatus
        switch ($params['newOrderStatus']->id) {
            case _PS_OS_PAYMENT_:
                $action = 'enable';
                break;
            case _PS_OS_ERROR_:
            case _PS_OS_CANCELED_:
            case _PS_OS_REFUND_:
                $action = 'disable';
                break;
        }

        if ($action) {
            $this->doCartRule($action, (int)$params['id_order']);
        }
    }

    private function doCartRule($action, $id_order)
    {
        $order = new Order($id_order);
        $details = $order->getOrderDetailList();
        $fop_vouchers_data = json_decode(Configuration::get('FOP_VOUCHERS_DATA'), true);

        if (!empty($fop_vouchers_data['id_product_list'])) {
            $id_products_coupon = explode(';', $fop_vouchers_data['id_product_list']);
            foreach ($details as $detail) {
                if (in_array($detail['product_id'], $id_products_coupon)) {
                    switch ($action) {
                        case 'enable':
                            $now = date('Y-m-d H:i:s');
                            $to = (!empty($fop_vouchers_data['voucher_duration']) ? $this->translateDuration($fop_vouchers_data['voucher_duration']) : $now);
                            if ($detail['product_quantity'] > 1) {
                                $count = 1;
                                while ($count <= (int)$detail['product_quantity']) {
                                    $cartRule = new CartRule();
                                    $cartRule->name[$order->id_lang] = $this->l('Fop Voucher') . ' - ' . $detail['product_name'];
                                    $cartRule->description = implode('_', array('coupon', $id_order, $detail['product_id'], $count)); ## detail coupon
                                    $cartRule->id_customer = $order->id_customer;
                                    $cartRule->date_from = $now;
                                    $cartRule->date_to = $to;
                                    $cartRule->priority = 1;
                                    $cartRule->quantity = 1;
                                    $cartRule->quantity_per_user = 1;
                                    $cartRule->cart_rule_restriction = 0;
                                    $cartRule->minimum_amount_currency = 1;
                                    $cartRule->partial_use = 0;
                                    $cartRule->code = $this->codeGen();
                                    $cartRule->reduction_amount = (float)$detail['unit_price_tax_incl'];
                                    $cartRule->reduction_tax = 1;
                                    $cartRule->reduction_currency = 1;
                                    if ($cartRule->add()) {
                                        $newFopVoucher = new VouchersModel();
                                        $newFopVoucher->id_order = (int)$id_order;
                                        $newFopVoucher->id_customer = (int)$order->id_customer;
                                        $newFopVoucher->id_product = (int)$detail['product_id'];
                                        $newFopVoucher->id_cart_rule = (int)$cartRule->id;
                                        $newFopVoucher->add();
                                    } else {
                                        PrestaShopLogger::addLog('fop_vouchers Module - error add: ' . $this->l('Fop Voucher') . ' - ' . $detail['product_name'], 1, null, 'CartRule', null, true);
                                    }
                                    $count++;
                                }
                            } else {
                                $cartRule = new CartRule();
                                $cartRule->name[$order->id_lang] = $this->l('Fop Voucher') . ' - ' . $detail['product_name'];
                                $cartRule->description = implode('_', array('coupon', $id_order, $detail['product_id'])); ## detail coupon
                                $cartRule->id_customer = $order->id_customer;
                                $cartRule->date_from = $now;
                                $cartRule->date_to = $to;
                                $cartRule->priority = 1;
                                $cartRule->quantity = 1;
                                $cartRule->quantity_per_user = 1;
                                $cartRule->cart_rule_restriction = 0;
                                $cartRule->minimum_amount_currency = 1;
                                $cartRule->partial_use = 0;
                                $cartRule->code = $this->codeGen();
                                $cartRule->reduction_amount = (float)$detail['total_price_tax_incl'];
                                $cartRule->reduction_tax = 1;
                                $cartRule->reduction_currency = 1;
                                if ($cartRule->add()) {
                                    $newFopVoucher = new VouchersModel();
                                    $newFopVoucher->id_order = (int)$id_order;
                                    $newFopVoucher->id_customer = (int)$order->id_customer;
                                    $newFopVoucher->id_product = (int)$detail['product_id'];
                                    $newFopVoucher->id_cart_rule = (int)$cartRule->id;
                                    $newFopVoucher->add();
                                } else {
                                    PrestaShopLogger::addLog('fop_vouchers Module - error add: ' . $this->l('Fop Voucher') . ' - ' . $detail['product_name'], 1, null, 'CartRule', null, true);
                                }
                            }
                            break;
                        case 'disable':
                            $cart_rules = VouchersModel::getCartRulesIds((int)$order->id_customer, (int)$id_order, (int)$detail['product_id']);
                            if (!empty($cart_rules)) {
                                foreach ($cart_rules as $id_fop_vouchers => $cart_rule) {
                                    $cart_rule->active = 0;
                                    if ($cart_rule->update()) {
                                        $fopVoucher = new VouchersModel((int)$id_fop_vouchers);
                                        $fopVoucher->delete();
                                    }
                                }
                            }
                            break;
                    }
                }
            }

            $customer = new Customer((int)$order->id_customer);
            if (!$this->sendVouchersByMail($customer, $order)) {
                PrestaShopLogger::addLog('fop_vouchers Module - mail error - id_order: ' . $id_order, 1, null, null, null, true);
            }
        }
    }

    private function getHtmlVoucher($type, $cart_rules, Order $order)
    {
        $html = '';
        $currency = new Currency($order->id_currency);
        foreach ($cart_rules as $cart_rule) {
            switch ($type) {
                case 'txt':
                    $html .= $this->l('Voucher amount') . ' : ' . $cart_rule->reduction_amount . "\n";
                    $html .= $this->l('Code') . ' : ' . $cart_rule->code . "\n";
                    $html .= $this->l('Validity') . ' : ' . $this->l('From') . ' ' . $cart_rule->date_from . ' ' . $this->l('to') . ' ' . $cart_rule->date_to . "\n";
                    $html .= "\r\n";
                    break;
                default:
                    $html .= '<table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-family: Open sans, Arial, sans-serif; font-size: 14px;">
                        <tbody>
                            <tr>
                                <td style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #fefefe; border: 1px solid #DFDFDF; vertical-align: top; padding-top: 10px; padding-bottom: 10px;" bgcolor="#fefefe">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-family: Open sans, Arial, sans-serif; font-size: 14px;" width="100%">
                                        <tr>
                                            <td align="left" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-size: 0px; padding: 10px 25px; word-break: break-word;">
                                                <div style="font-family:Open sans, arial, sans-serif;font-size:16px;line-height:25px;text-align:left;color:#363A41;" align="left">
                                                    <span class="label" style="font-weight: 700;">' . $this->l('Voucher amount') . ' :</span> ' . Tools::displayPrice($cart_rule->reduction_amount, $currency) . '
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-size: 0px; padding: 10px 25px; word-break: break-word;">
                                                <div style="font-family:Open sans, arial, sans-serif;font-size:16px;line-height:25px;text-align:left;color:#363A41;" align="left">
                                                    <span class="label" style="font-weight: 700;">' . $this->l('Code') . ' :</span> ' . $cart_rule->code . '
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-size: 0px; padding: 10px 25px; word-break: break-word;">
                                                <div style="font-family:Open sans, arial, sans-serif;font-size:16px;line-height:25px;text-align:left;color:#363A41;" align="left">
                                                    <span class="label" style="font-weight: 700;">' . $this->l('Validity') . ' :</span> ' . $this->l('From') . ' ' . $cart_rule->date_from . ' ' . $this->l('to') . ' ' . $cart_rule->date_to . '
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
            </table>';
            }
        }
        return $html;
    }

    private function sendVouchersByMail(Customer $customer, Order $order)
    {
        $cart_rules = VouchersModel::getCartRulesIds($customer->id, $order->id);
        if (!empty($cart_rules)) {
            $tpl_vars = array(
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{html_vouchers}' => $this->getHtmlVoucher('html', $cart_rules, $order),
                '{txt_vouchers}' => $this->getHtmlVoucher('txt', $cart_rules, $order)
            );
            return Mail::Send(
                (int)$order->id_lang,
                'fop_voucher_confirmation',
                $this->l('Your vouchers'),
                $tpl_vars,
                $customer->email,
                $customer->firstname . ' ' . $customer->lastname,
                null,
                null,
                null,
                null,
                $this->getLocalPath() . 'mails/',
                null,
                (int)$order->id_shop
            );
        }
    }

    private function codeGen()
    {
        $code = Tools::passwdGen(6);
        if (!CartRule::cartRuleExists($code)) {
            return Tools::strtoupper($code);
        } else {
            return Tools::strtoupper(Tools::passwdGen(12));
        }
    }

    private function translateDuration($duration)
    {
        list($quantity, $type) = explode(';', $duration);
        $type = Tools::strtolower($type);
        switch ($type) {
            case 'y':
                $date_type = 'year';
                break;
            case 'd':
                $date_type = 'day';
                break;
            case 'h':
                $date_type = 'hour';
                break;
            case 'w':
                $date_type = 'weeks';
                break;
            case 'm':
            default:
                $date_type = 'month';
        }
        if ($quantity > 1 && $type != 'w') {
            $date_type .= 's';
        }
        $date = new DateTime('now');
        $date->modify("+$quantity $date_type");
        return $date->format('Y-m-d h:i:s');
    }
}
