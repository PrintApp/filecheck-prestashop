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

class FilecheckJob
{
    public static function getJobById($id_filecheck_job)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'filecheck_jobs` WHERE `id_filecheck_job` = ' . (int)$id_filecheck_job;
        return Db::getInstance()->getRow($sql);
    }

    /**
     * Save/update Filecheck jobId associated with a cart product
     */
    public static function saveJobForCart($id_cart, $id_product, $id_product_attribute, $job_id)
    {
        $id_cart = (int)$id_cart;
        $id_product = (int)$id_product;
        $id_product_attribute = (int)$id_product_attribute;
        $job_id = pSQL($job_id);

        if (!$id_cart || !$id_product || empty($job_id)) {
            return false;
        }

        // Delete any existing job for this cart and product to avoid duplicate old runs
        Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . 'filecheck_jobs` 
            WHERE `id_cart` = ' . $id_cart . ' 
            AND `id_product` = ' . $id_product . ' 
            AND `id_product_attribute` = ' . $id_product_attribute . '
            AND `id_order` = 0
        ');

        return Db::getInstance()->insert(
            'filecheck_jobs',
            [
                'id_cart' => $id_cart,
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'job_id' => $job_id,
                'id_order' => 0,
                'synced' => 0,
                'processed' => 0,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Associate jobs linked to a cart with the placed order ID
     */
    public static function associateJobsWithOrder($id_cart, $id_order)
    {
        return Db::getInstance()->update(
            'filecheck_jobs',
            [
                'id_order' => (int)$id_order,
                'date_upd' => date('Y-m-d H:i:s'),
            ],
            'id_cart = ' . (int)$id_cart . ' AND id_order = 0'
        );
    }

    /**
     * Fetch jobs associated with an order ID
     */
    public static function getJobsForOrder($id_order)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'filecheck_jobs` WHERE `id_order` = ' . (int)$id_order;
        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * Update downloaded secure files list
     */
    public static function updateDownloadedFiles($id_filecheck_job, $downloaded_files)
    {
        return Db::getInstance()->update(
            'filecheck_jobs',
            [
                'downloaded_files' => pSQL(json_encode($downloaded_files)),
                'date_upd' => date('Y-m-d H:i:s'),
            ],
            'id_filecheck_job = ' . (int)$id_filecheck_job
        );
    }

    /**
     * Mark jobs verified as synced
     */
    public static function markSynced($id_order)
    {
        return Db::getInstance()->update(
            'filecheck_jobs',
            [
                'synced' => 1,
                'date_upd' => date('Y-m-d H:i:s'),
            ],
            'id_order = ' . (int)$id_order
        );
    }

    /**
     * Mark runs as fully downloaded & processed
     */
    public static function markProcessed($id_order)
    {
        return Db::getInstance()->update(
            'filecheck_jobs',
            [
                'processed' => 1,
                'date_upd' => date('Y-m-d H:i:s'),
            ],
            'id_order = ' . (int)$id_order
        );
    }

    /**
     * Check if files are already processed
     */
    public static function isProcessed($id_order)
    {
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'filecheck_jobs` 
                WHERE `id_order` = ' . (int)$id_order . ' AND `processed` = 0';
        $unprocessed_count = (int)Db::getInstance()->getValue($sql);
        
        return $unprocessed_count === 0;
    }
}
