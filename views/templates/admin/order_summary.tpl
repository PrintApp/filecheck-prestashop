{*
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
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
 * @author    Filecheck <support@filecheck.io>
 * @copyright Since 2026 Filecheck
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

<div class="panel card" style="margin-top: 20px;">
    <div class="panel-heading card-header" style="font-size: 16px; font-weight: bold; background: #fcfcfc; padding: 12px 20px; border-bottom: 1px solid #eee;">
        <i class="icon-file-text"></i> {l s='Filecheck Uploads' d='Modules.Filecheck.Admin'}
    </div>
    <div class="panel-body card-body" style="padding: 20px;" id="filecheck-job-panel-wrapper">
        <div id="filecheck-job-panel" data-order-id="{$fc_order_id|escape:'html':'UTF-8'}">
            <p class="filecheck-loading"><i class="icon-spinner icon-spin"></i> {l s='Loading file details…' d='Modules.Filecheck.Admin'}</p>
        </div>
    </div>
</div>

<script type="text/javascript">
    var ajax_order_url = "{$ajax_order_url|escape:'javascript':'UTF-8'}";
</script>
