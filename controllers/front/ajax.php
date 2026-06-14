<?php
/**
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
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class FilecheckAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        ob_end_clean();
        header('Content-Type: application/json');

        $action = Tools::getValue('action');
        $id_product = (int) Tools::getValue('product_id');
        $job_id = Tools::getValue('job_id');

        if ($action === 'filecheck_save_job') {
            if (!$id_product) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid product ID'
                ]);
                exit;
            }

            $cookie_key = 'fc_job_' . $id_product;

            if ($job_id) {
                $this->context->cookie->$cookie_key = $job_id;
                // Save it immediately to the active cart if we have one
                if (Validate::isLoadedObject($this->context->cart)) {
                    $id_product_attribute = (int) Tools::getValue('product_attribute_id', 0);
                    FilecheckJob::saveJobForCart(
                        $this->context->cart->id,
                        $id_product,
                        $id_product_attribute,
                        $job_id
                    );
                }
            } else {
                if (isset($this->context->cookie->$cookie_key)) {
                    unset($this->context->cookie->$cookie_key);
                }
            }

            $this->context->cookie->write();

            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode([
            'success' => false,
            'message' => 'Invalid Action'
        ]);
        exit;
    }
}
