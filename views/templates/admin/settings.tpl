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

<div class="wrap">
    <div style="display: flex; align-items: center; margin-bottom: 20px; padding: 10px 0; border-bottom: 1px solid #ccd0d4;">
        <img src="{$filecheck_logo|escape:'html':'UTF-8'}" style="width: 42px; height: 42px; margin-right: 15px;" alt="Filecheck Logo">
        <h1 style="margin: 0; font-size: 24px; font-weight: 600; line-height: 42px; color: #1d2327;">{l s='Filecheck Settings' d='Modules.Filecheck.Admin'}</h1>
    </div>

    <form method="post" action="" class="defaultForm form-horizontal">
        <div class="panel" style="max-width: 800px; margin-bottom: 20px;">
            <div class="panel-heading">
                <i class="icon-key"></i> {l s='API Credentials' d='Modules.Filecheck.Admin'}
            </div>
            
            <div class="form-wrapper">
                <div class="form-group">
                    <label class="control-label col-lg-3Required col-lg-3" style="text-align: right; font-weight: bold;">
                        {l s='Publishable Key' d='Modules.Filecheck.Admin'}
                    </label>
                    <div class="col-lg-9">
                        <input type="text" name="FILECHECK_PUBLISHABLE_KEY" id="filecheck_publishable_key" value="{$FILECHECK_PUBLISHABLE_KEY|escape:'html':'UTF-8'}" class="fixed-width-xxl" style="width: 100%; max-width: 450px;">
                        <p class="help-block">{l s='Your Filecheck Publishable Key (pk_live_... or pk_test_...).' d='Modules.Filecheck.Admin'}</p>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label class="control-label col-lg-3Required col-lg-3" style="text-align: right; font-weight: bold;">
                        {l s='Secret Key' d='Modules.Filecheck.Admin'}
                    </label>
                    <div class="col-lg-9">
                        <input type="password" name="FILECHECK_SECRET_KEY" id="filecheck_secret_key" value="{$FILECHECK_SECRET_KEY|escape:'html':'UTF-8'}" class="fixed-width-xxl" style="width: 100%; max-width: 450px;">
                        <p class="help-block">{l s='Your Filecheck Secret Key (sk_live_... or sk_test_...). Keep this private.' d='Modules.Filecheck.Admin'}</p>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label class="control-label col-lg-3" style="text-align: right;">
                        {l s='Agent ID (Optional)' d='Modules.Filecheck.Admin'}
                    </label>
                    <div class="col-lg-9">
                        <input type="text" name="FILECHECK_AGENT_ID" id="filecheck_agent_id" value="{$FILECHECK_AGENT_ID|escape:'html':'UTF-8'}" class="fixed-width-xxl" style="width: 100%; max-width: 450px;">
                        <p class="help-block">{l s='Optional sub-tenant or agent identifier.' d='Modules.Filecheck.Admin'}</p>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label class="control-label col-lg-3" style="text-align: right;">
                        {l s='API Base URL' d='Modules.Filecheck.Admin'}
                    </label>
                    <div class="col-lg-9">
                        <input type="text" name="FILECHECK_API_URL" id="filecheck_api_url" value="{$FILECHECK_API_URL|escape:'html':'UTF-8'}" class="fixed-width-xxl" style="width: 100%; max-width: 450px;">
                        <p class="help-block">{l s='Override URL for development environment. Default is https://api.filecheck.io' d='Modules.Filecheck.Admin'}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel" style="max-width: 800px; margin-bottom: 20px;">
            <div class="panel-heading">
                <i class="icon-cogs"></i> {l s='Global Configuration' d='Modules.Filecheck.Admin'}
            </div>

            <div class="form-wrapper">
                <div class="form-group">
                    <label class="control-label col-lg-3" style="text-align: right; font-weight: bold;">
                        {l s='Default Workflow' d='Modules.Filecheck.Admin'}
                    </label>
                    <div class="col-lg-9">
                        <select name="FILECHECK_DEFAULT_WORKFLOW_ID" id="filecheck_default_workflow_id" style="width: 100%; max-width: 450px; height: 35px;">
                            <option value="">{l s='Select a workflow...' d='Modules.Filecheck.Admin'}</option>
                            {if is_array($workflows)}
                                {foreach from=$workflows item=workflow}
                                    {if isset($workflow.id) && isset($workflow.title)}
                                        <option value="{$workflow.id|escape:'html':'UTF-8'}" {if $FILECHECK_DEFAULT_WORKFLOW_ID == $workflow.id}selected="selected"{/if}>
                                            {$workflow.title|escape:'html':'UTF-8'}
                                        </option>
                                    {/if}
                                {/foreach}
                            {/if}
                        </select>
                        <p class="help-block">{l s='Default workflow. Can be overridden per product.' d='Modules.Filecheck.Admin'}</p>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <button type="submit" name="submitFilecheckModule" class="btn btn-default button" style="background-color: #2eacd5; color: #fff; border-color: #2eacd5; padding: 10px 20px; font-size: 14px; font-weight: bold; border-radius: 4px;">
                <i class="icon-save" style="margin-right: 5px;"></i> {l s='Save Settings' d='Modules.Filecheck.Admin'}
            </button>
        </div>
    </form>

    <div class="panel" style="max-width: 800px; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div class="panel-heading" style="margin-bottom: 10px; font-size: 16px; font-weight: bold;">
            <i class="icon-check"></i> {l s='Connection Status' d='Modules.Filecheck.Admin'}
        </div>
        <p>{l s='Test if your API keys can communicate with the Filecheck servers.' d='Modules.Filecheck.Admin'}</p>
        <button type="button" id="filecheck-test-connection" class="btn btn-default" style="padding: 8px 15px; border-radius: 4px;">{l s='Test Connection' d='Modules.Filecheck.Admin'}</button>
        <span id="filecheck-connection-result" style="margin-left: 15px; font-weight: 500; display: inline-block; vertical-align: middle;"></span>
    </div>
</div>

<script type="text/javascript">
    var ajax_test_url = "{$ajax_test_url|escape:'javascript':'UTF-8'}";
</script>
