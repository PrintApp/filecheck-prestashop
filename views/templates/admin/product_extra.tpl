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

<div class="product-tab-content" id="filecheck_product_data" style="padding: 20px; background: #fff; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px; font-weight: 600;">
        <i class="icon-file-text"></i> {l s='Filecheck Settings' d='Modules.Filecheck.Admin'}
    </h3>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="control-label" style="font-weight: bold; margin-bottom: 5px;">
                    {l s='Workflow' d='Modules.Filecheck.Admin'}
                </label>
                <select name="filecheck_workflow_id" id="filecheck_workflow_id" class="form-control" style="max-width: 400px; height: 35px;">
                    <option value="none" {if $fc_prod_workflow_id == 'none'}selected="selected"{/if}>{l s='None (Disabled)' d='Modules.Filecheck.Admin'}</option>
                    <option value="global" {if $fc_prod_workflow_id == 'global'}selected="selected"{/if}>{l s='Use Global Default' d='Modules.Filecheck.Admin'}</option>
                    {if is_array($fc_workflows)}
                        {foreach from=$fc_workflows item=workflow}
                            {if isset($workflow.id) && isset($workflow.title)}
                                <option value="{$workflow.id|escape:'html':'UTF-8'}" {if $fc_prod_workflow_id == $workflow.id}selected="selected"{/if}>
                                    {$workflow.title|escape:'html':'UTF-8'}
                                </option>
                            {/if}
                        {/foreach}
                    {/if}
                </select>
                <p class="help-block" style="color: #666; font-size: 11px; margin-top: 3px;">
                    {l s='Select the workflow to validate uploads against. "None" disables Filecheck for this product.' d='Modules.Filecheck.Admin'}
                </p>
            </div>

            <div class="form-group" style="margin-bottom: 15px; margin-top: 15px;">
                <label class="control-label" style="font-weight: bold; margin-bottom: 5px;">
                    {l s='Connector' d='Modules.Filecheck.Admin'}
                </label>
                <select name="filecheck_connector_id" id="filecheck_connector_id" class="form-control" style="max-width: 400px; height: 35px;">
                    <option value="" {if $fc_prod_connector_id == ''}selected="selected"{/if}>{l s='None' d='Modules.Filecheck.Admin'}</option>
                    {if is_array($fc_connectors)}
                        {foreach from=$fc_connectors item=connector}
                            {if isset($connector.id) && isset($connector.title)}
                                <option value="{$connector.id|escape:'html':'UTF-8'}" {if $fc_prod_connector_id == $connector.id}selected="selected"{/if}>
                                    {$connector.title|escape:'html':'UTF-8'} ({$connector.id|escape:'html':'UTF-8'})
                                </option>
                            {/if}
                        {/foreach}
                    {/if}
                </select>
                <p class="help-block" style="color: #666; font-size: 11px; margin-top: 3px;">
                    {l s='Optional. Syncs Filecheck file details to elements on this product page.' d='Modules.Filecheck.Admin'}
                </p>
            </div>

            <div class="form-group" style="margin-bottom: 15px; margin-top: 15px;">
                <label class="control-label" style="font-weight: bold; margin-bottom: 5px;">
                    {l s='Presentation Mode' d='Modules.Filecheck.Admin'}
                </label>
                <select name="filecheck_presentation" id="filecheck_presentation" class="form-control" style="max-width: 400px; height: 35px;">
                    <option value="" {if $fc_prod_presentation == ''}selected="selected"{/if}>{l s='Use Global Default' d='Modules.Filecheck.Admin'}</option>
                    <option value="inline" {if $fc_prod_presentation == 'inline'}selected="selected"{/if}>{l s='Inline (embedded in product page)' d='Modules.Filecheck.Admin'}</option>
                    <option value="dialog" {if $fc_prod_presentation == 'dialog'}selected="selected"{/if}>{l s='Dialog (button opens modal overlay)' d='Modules.Filecheck.Admin'}</option>
                </select>
                <p class="help-block" style="color: #666; font-size: 11px; margin-top: 3px;">
                    {l s='Choose how the Filecheck element is displayed on the product page.' d='Modules.Filecheck.Admin'}
                </p>
            </div>
        </div>
    </div>
</div>
