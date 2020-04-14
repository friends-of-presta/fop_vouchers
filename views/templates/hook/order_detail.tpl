{*
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
*}
<section id="fop-vouchers" class="box">
    <h3>{l s='Your vouchers codes' mod='fop_vouchers'}</h3>
    <table class="table table-striped table-bordered table-labeled hidden-xs-down">
        <thead class="thead-default">
            <tr>
                <th>Code</th>
                <th>montant</th>
                <th>Validité</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$vouchers item=voucher}
            <tr>
                <td><strong>{$voucher->code}</strong></td>
                <td>{$voucher->reduction_amount}</td>
                <td>{l s='From' mod='fop_vouchers'} <strong>{$voucher->date_from}</strong> {l s='to ' mod='fop_vouchers'} <strong>{$voucher->date_from}</strong></td>
            </tr>
            {/foreach}
        </tbody>
    </table>
</section>