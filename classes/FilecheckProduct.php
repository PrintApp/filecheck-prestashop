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

class FilecheckProduct
{
    /** Artwork after checkout: when a product collects its artwork. */
    const TIMINGS = ['checkout', 'either', 'after_order'];

    /**
     * Per-product timings, kept as one JSON map in Configuration so existing
     * installs need no table change. Missing = the global default.
     */
    protected static function timings()
    {
        $map = json_decode((string) Configuration::get('FILECHECK_PRODUCT_TIMINGS'), true);
        return is_array($map) ? $map : [];
    }

    public static function getTiming($id_product)
    {
        $map = self::timings();
        $timing = $map[(string) (int) $id_product] ?? '';
        return in_array($timing, self::TIMINGS, true) ? $timing : '';
    }

    public static function saveTiming($id_product, $timing)
    {
        $map = self::timings();
        $key = (string) (int) $id_product;
        if (in_array($timing, self::TIMINGS, true)) {
            $map[$key] = $timing;
        } else {
            unset($map[$key]);
        }
        return Configuration::updateValue('FILECHECK_PRODUCT_TIMINGS', json_encode($map));
    }

    /** Resolved timing: the product's, then the global default, then 'checkout'. */
    public static function artworkTiming($id_product)
    {
        $timing = self::getTiming($id_product) ?: Configuration::get('FILECHECK_ARTWORK_TIMING');
        return in_array($timing, self::TIMINGS, true) ? $timing : 'checkout';
    }

    /** The product's workflow id with "global" resolved; '' when Filecheck is off for it. */
    public static function workflowId($id_product)
    {
        $workflow_id = self::getForProduct($id_product)['workflow_id'];
        if ($workflow_id === 'none') {
            return '';
        }
        if (empty($workflow_id) || $workflow_id === 'global') {
            $workflow_id = Configuration::get('FILECHECK_DEFAULT_WORKFLOW_ID');
        }
        return $workflow_id ?: '';
    }

    /**
     * Get Filecheck configuration for a specific product.
     * Returns an array with workflow_id, connector_id and presentation settings.
     */
    public static function getForProduct($id_product)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'filecheck_product` WHERE `id_product` = ' . (int)$id_product;
        $row = Db::getInstance()->getRow($sql);

        if (!$row) {
            return [
                'id_product' => (int)$id_product,
                'workflow_id' => 'global',
                'connector_id' => '',
                'presentation' => '',
            ];
        }

        return [
            'id_product' => (int)$row['id_product'],
            'workflow_id' => $row['workflow_id'] ?: 'global',
            'connector_id' => $row['connector_id'] ?: '',
            'presentation' => $row['presentation'] ?: '',
        ];
    }

    /**
     * Save Filecheck configurations for a specific product.
     */
    public static function saveForProduct($id_product, $workflow_id, $connector_id, $presentation)
    {
        $id_product = (int)$id_product;
        if (!$id_product) {
            return false;
        }

        $workflow_id = pSQL($workflow_id);
        $connector_id = pSQL($connector_id);
        $presentation = pSQL($presentation);

        $exists = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'filecheck_product` WHERE `id_product` = ' . $id_product
        );

        if ($exists) {
            return Db::getInstance()->update(
                'filecheck_product',
                [
                    'workflow_id' => $workflow_id,
                    'connector_id' => $connector_id,
                    'presentation' => $presentation,
                ],
                'id_product = ' . $id_product
            );
        } else {
            return Db::getInstance()->insert(
                'filecheck_product',
                [
                    'id_product' => $id_product,
                    'workflow_id' => $workflow_id,
                    'connector_id' => $connector_id,
                    'presentation' => $presentation,
                ]
            );
        }
    }
}
