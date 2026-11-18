<?php
/**
 * 1.1.0: artwork after checkout. Registers the upload link hooks on
 * existing installs (new installs register them in install()).
 *
 * @author    Filecheck <support@filecheck.io>
 * @copyright Since 2026 Filecheck
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    if (Configuration::get('FILECHECK_UPLOAD_EMAIL') === false) {
        Configuration::updateValue('FILECHECK_UPLOAD_EMAIL', 1);
    }
    return $module->registerUploadLinkHooks();
}
