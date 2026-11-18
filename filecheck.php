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

// Require our classes
require_once dirname(__FILE__) . '/classes/FilecheckAPIClient.php';
require_once dirname(__FILE__) . '/classes/FilecheckProduct.php';
require_once dirname(__FILE__) . '/classes/FilecheckJob.php';

class Filecheck extends Module
{
    protected $config_fields = [
        'FILECHECK_PUBLISHABLE_KEY',
        'FILECHECK_SECRET_KEY',
        'FILECHECK_AGENT_ID',
        'FILECHECK_API_URL',
        'FILECHECK_DEFAULT_WORKFLOW_ID',
    ];

    public function __construct()
    {
        $this->name = 'filecheck';
        $this->tab = 'adverts_market_places';
        $this->version = '1.1.0';
        $this->author = 'Filecheck';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Filecheck');
        $this->description = $this->l('Integrates the Filecheck preflight widget on product add-to-cart actions.');

        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => '8.9.99'];
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Filecheck?');
    }

    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to use this module.');
            return false;
        }

        // Default API url
        Configuration::updateValue('FILECHECK_API_URL', 'https://api.filecheck.io');

        return parent::install()
            && $this->registerHooks()
            && $this->createTables();
    }

    public function uninstall()
    {
        foreach ($this->config_fields as $field) {
            Configuration::deleteByName($field);
        }

        return parent::uninstall()
            && $this->deleteTables();
    }

    protected function registerHooks()
    {
        return $this->registerHook('displayHeader')
            && $this->registerHook('displayProductActions')
            && $this->registerHook('actionCartSave')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionOrderStatusUpdate')
            && $this->registerHook('displayAdminOrder')
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerUploadLinkHooks();
    }

    /**
     * Artwork after checkout: the upload link on the order confirmation page,
     * the order detail page, and as {filecheck_upload_link} in store emails.
     * Also registered by upgrade/upgrade-1.1.0.php on existing installs.
     */
    public function registerUploadLinkHooks()
    {
        return $this->registerHook('displayOrderConfirmation')
            && $this->registerHook('displayOrderDetail')
            && $this->registerHook('actionEmailSendBefore');
    }

    protected function createTables()
    {
        $sql = [];

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'filecheck_product` (
            `id_product` INT(11) UNSIGNED NOT NULL,
            `workflow_id` VARCHAR(255) DEFAULT NULL,
            `connector_id` VARCHAR(255) DEFAULT NULL,
            `presentation` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`id_product`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'filecheck_jobs` (
            `id_filecheck_job` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_cart` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `id_product` INT(11) UNSIGNED NOT NULL,
            `id_product_attribute` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `job_id` VARCHAR(255) NOT NULL,
            `id_order` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `downloaded_files` TEXT DEFAULT NULL,
            `synced` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            `processed` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_filecheck_job`),
            KEY `id_cart` (`id_cart`),
            KEY `id_order` (`id_order`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return true;
    }

    protected function deleteTables()
    {
        $sql = [
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'filecheck_product`',
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'filecheck_jobs`',
        ];

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Renders settings page inside back-office
     */
    public function getContent()
    {
        // Explicitly route and process any background AJAX actions before rendering views
        $this->postProcess();

        $output = '';

        if (Tools::isSubmit('submitFilecheckModule')) {
            $pk = trim(Tools::getValue('FILECHECK_PUBLISHABLE_KEY'));
            $sk = trim(Tools::getValue('FILECHECK_SECRET_KEY'));
            $agent = trim(Tools::getValue('FILECHECK_AGENT_ID'));
            $url = trim(Tools::getValue('FILECHECK_API_URL'));
            $workflow = trim(Tools::getValue('FILECHECK_DEFAULT_WORKFLOW_ID'));

            Configuration::updateValue('FILECHECK_PUBLISHABLE_KEY', $pk);
            Configuration::updateValue('FILECHECK_SECRET_KEY', $sk);
            Configuration::updateValue('FILECHECK_AGENT_ID', $agent);
            Configuration::updateValue('FILECHECK_API_URL', $url);
            Configuration::updateValue('FILECHECK_DEFAULT_WORKFLOW_ID', $workflow);
            $timing = Tools::getValue('FILECHECK_ARTWORK_TIMING');
            Configuration::updateValue('FILECHECK_ARTWORK_TIMING', in_array($timing, FilecheckProduct::TIMINGS, true) ? $timing : 'checkout');
            Configuration::updateValue('FILECHECK_UPLOAD_EMAIL', Tools::getValue('FILECHECK_UPLOAD_EMAIL') ? 1 : 0);

            $output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
        }

        $this->context->smarty->assign([
            'filecheck_logo' => $this->getPathUri() . 'views/img/icon.svg',
            'FILECHECK_PUBLISHABLE_KEY' => Configuration::get('FILECHECK_PUBLISHABLE_KEY'),
            'FILECHECK_SECRET_KEY' => Configuration::get('FILECHECK_SECRET_KEY'),
            'FILECHECK_AGENT_ID' => Configuration::get('FILECHECK_AGENT_ID'),
            'FILECHECK_API_URL' => Configuration::get('FILECHECK_API_URL', 'https://api.filecheck.io'),
            'FILECHECK_DEFAULT_WORKFLOW_ID' => Configuration::get('FILECHECK_DEFAULT_WORKFLOW_ID'),
            'FILECHECK_ARTWORK_TIMING' => Configuration::get('FILECHECK_ARTWORK_TIMING') ?: 'checkout',
            'FILECHECK_UPLOAD_EMAIL' => (int) Configuration::get('FILECHECK_UPLOAD_EMAIL', null, null, null, 1),
            'workflows' => FilecheckAPIClient::instance()->getWorkflows(),
            'ajax_test_url' => $this->context->link->getAdminLink('AdminModules', true, [], [
                'configure' => $this->name,
                'action' => 'testConnection',
            ]),
        ]);

        return $output . $this->display(__FILE__, 'views/templates/admin/settings.tpl');
    }

    /**
     * Handle AJAX connection testing from settings page hook / dispatch controller path
     */
    public function postProcess()
    {
        if (Tools::getValue('action') === 'testConnection') {
            ob_end_clean();
            header('Content-Type: application/json');

            $pubKey = Tools::getValue('publishable_key');
            $secKey = Tools::getValue('secret_key');

            if (empty($pubKey) || empty($secKey)) {
                echo json_encode([
                    'success' => false,
                    'message' => $this->l('Both Publishable Key and Secret Key are required to test connection.')
                ]);
                exit;
            }

            $result = FilecheckAPIClient::instance()->verifyKeys($pubKey, $secKey);

            if ($result === true) {
                echo json_encode([
                    'success' => true,
                    'message' => $this->l('Connection successful! Keys are valid.')
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result instanceof PrestaShopException || is_string($result) ? (is_string($result) ? $result : $result->getMessage()) : $this->l('Authentication failed. Please check your keys.')
                ]);
            }
            exit;
        }

        if (Tools::getValue('action') === 'getOrderJobDetails') {
            $this->ajaxProcessGetOrderJobDetails();
        }

        if (Tools::getValue('action') === 'downloadSecureFile') {
            $this->ajaxProcessDownloadSecureFile();
        }
    }

    // -------------------------------------------------------------
    // HOOKS HANDLERS (To be detailed when implementing points 5 & 6)
    // -------------------------------------------------------------

    public function hookDisplayHeader()
    {
        if (!($this->context->controller instanceof ProductController)) {
            return;
        }

        $id_product = (int) Tools::getValue('id_product');
        if (!$id_product) {
            return;
        }

        $prod_config = FilecheckProduct::getForProduct($id_product);
        $workflow_id = $prod_config['workflow_id'];

        if ($workflow_id === 'none') {
            return;
        }

        $pk = Configuration::get('FILECHECK_PUBLISHABLE_KEY');
        if (empty($pk)) {
            return;
        }

        if (empty($workflow_id) || $workflow_id === 'global') {
            $workflow_id = Configuration::get('FILECHECK_DEFAULT_WORKFLOW_ID');
        }

        if (empty($workflow_id)) {
            return;
        }

        $agent_id = Configuration::get('FILECHECK_AGENT_ID');
        $connector_id = $prod_config['connector_id'];

        $this->context->controller->registerJavascript(
            'filecheck-cdn-element',
            'https://cdn.filecheck.io/element/' . rawurlencode($pk) . '/filecheck.js',
            ['server' => 'remote', 'position' => 'head', 'priority' => 1]
        );

        $this->context->controller->registerJavascript(
            'module-filecheck-frontend',
            'modules/' . $this->name . '/views/js/frontend.js',
            ['position' => 'bottom', 'priority' => 100]
        );

        $this->context->controller->registerStylesheet(
            'module-filecheck-frontend-css',
            'modules/' . $this->name . '/views/css/frontend.css',
            ['media' => 'all', 'priority' => 100]
        );

        $ajax_endpoint = $this->context->link->getModuleLink($this->name, 'ajax', [], true);

        Media::addJsDef([
            'filecheck_params' => [
                'publishable_key' => $pk,
                'agent_id' => $agent_id,
                'workflow_id' => $workflow_id,
                'connector_id' => $connector_id,
                'artwork_timing' => FilecheckProduct::artworkTiming($id_product),
                'id_product' => $id_product,
                'ajax_url' => $ajax_endpoint,
            ]
        ]);
    }

    public function hookDisplayProductActions()
    {
        $id_product = (int) Tools::getValue('id_product');
        if (!$id_product) {
            return '';
        }

        $prod_config = FilecheckProduct::getForProduct($id_product);
        $workflow_id = $prod_config['workflow_id'];

        if ($workflow_id === 'none') {
            return '';
        }

        if (empty($workflow_id) || $workflow_id === 'global') {
            $workflow_id = Configuration::get('FILECHECK_DEFAULT_WORKFLOW_ID');
        }

        if (empty($workflow_id)) {
            if ($this->context->employee) {
                return '<div style="color:red; margin: 10px 0;">Filecheck error: Global default workflow is not configured.</div>';
            }
            return '';
        }

        $this->context->smarty->assign([
            'fc_product_id' => $id_product,
            'fc_workflow_id' => $workflow_id,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product_widget.tpl');
    }

    /**
     * Intercept cart saves to map uploaded job to cart item
     */
    public function hookActionCartSave($params)
    {
        $cart = isset($params['cart']) ? $params['cart'] : $this->context->cart;
        if (!Validate::isLoadedObject($cart)) {
            return;
        }

        // First, check if there's a filecheck_job_id in the POST request (typical add-to-cart context)
        $job_id = Tools::getValue('filecheck_job_id');
        $id_product = (int) Tools::getValue('id_product');
        $id_product_attribute = (int) Tools::getValue('id_product_attribute');

        if (empty($job_id) && $id_product) {
            // Check fallback cookie/session save
            $cookie_key = 'fc_job_' . $id_product;
            if (isset($this->context->cookie->$cookie_key)) {
                $job_id = $this->context->cookie->$cookie_key;
            }
        }

        if ($job_id && $id_product) {
            FilecheckJob::saveJobForCart(
                $cart->id,
                $id_product,
                $id_product_attribute,
                $job_id
            );

            // Clean cookie to prevent accidental reuse
            $cookie_key = 'fc_job_' . $id_product;
            if (isset($this->context->cookie->$cookie_key)) {
                unset($this->context->cookie->$cookie_key);
                $this->context->cookie->write();
            }
        }
    }

    /**
     * Hook before/after validating/creating order
     */
    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $cart = $params['cart'];

        if (!Validate::isLoadedObject($order) || !Validate::isLoadedObject($cart)) {
            return;
        }

        // Move jobs from id_cart to id_order
        FilecheckJob::associateJobsWithOrder($cart->id, $order->id);

        // Sync order details to Filecheck API. The order's own state is not set
        // yet at this hook, so pass the status the order is being created with.
        $id_state = isset($params['orderStatus']) && Validate::isLoadedObject($params['orderStatus']) ? (int) $params['orderStatus']->id : 0;
        $this->syncOrderToFilecheck($order, null, $id_state);
    }

    /**
     * Sync status updates and process/download outputs if order shifts to process or completed
     */
    public function hookActionOrderStatusUpdate($params)
    {
        $new_status = $params['newOrderStatus'];
        $id_order = (int) $params['id_order'];

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            return;
        }

        // Get status name/state
        $state = $new_status->name[$this->context->language->id] ?? 'Unknown';

        // Re-sync status to Filecheck
        $this->syncOrderToFilecheck($order, $state, (int) $new_status->id);

        // Standard Paid / Processing / Completed triggers file downloads
        // In PrestaShop, we can inspect if the order state is marked as paid or paid status
        if ($new_status->logable || $new_status->paid || in_array($new_status->id, [
            (int) Configuration::get('PS_OS_PAYMENT'),
            (int) Configuration::get('PS_OS_PREPARATION'),
            (int) Configuration::get('PS_OS_SHIPPING'),
            (int) Configuration::get('PS_OS_DELIVERED')
        ])) {
            $this->processOrderFiles($order);
        }
    }

    /**
     * Order statuses at which lines bought without artwork are NOT collected:
     * a failed payment, a cancelled or a refunded order never gets an upload page.
     */
    protected function collectsArtworkLater($id_state)
    {
        $closed = array_map('intval', [
            Configuration::get('PS_OS_ERROR'),
            Configuration::get('PS_OS_CANCELED'),
            Configuration::get('PS_OS_REFUND'),
        ]);
        return !in_array((int) $id_state, $closed, true);
    }

    public function syncOrderToFilecheck($order, $custom_status = null, $id_state = 0)
    {
        $secret_key = Configuration::get('FILECHECK_SECRET_KEY');
        if (empty($secret_key)) {
            return;
        }

        $jobs = FilecheckJob::getJobsForOrder($order->id) ?: [];
        $products = $order->getProducts();
        $line_items = [];
        $awaiting = false;
        $collect_later = $this->collectsArtworkLater($id_state ?: (int) $order->current_state);

        foreach ($products as $p) {
            $matching_job = null;
            foreach ($jobs as $job) {
                if ((int)$job['id_product'] === (int)$p['product_id'] 
                    && (string)$job['job_id'] !== '') {
                    $matching_job = $job['job_id'];
                    break;
                }
            }

            // Artwork after checkout: a line bought without a file, on a product
            // that collects artwork after the order, goes to Filecheck too.
            $await_workflow = '';
            if (!$matching_job) {
                $await_workflow = $collect_later ? FilecheckProduct::workflowId((int) $p['product_id']) : '';
                if (!$await_workflow || FilecheckProduct::artworkTiming((int) $p['product_id']) === 'checkout') {
                    continue;
                }
                $awaiting = true;
            }

            $line = [
                'itemId' => (string) $p['id_order_detail'],
                'productId' => (string) $p['product_id'],
                'name' => $p['product_name'],
                'quantity' => (int) $p['product_quantity'],
                'sku' => $p['product_reference'],
                'total' => (float) $p['total_price_tax_incl'],
                'jobId' => $matching_job ?: null,
            ];
            if ($await_workflow) {
                $line['awaitArtwork'] = true;
                $line['workflowId'] = $await_workflow;
            }
            $line_items[] = $line;
        }

        if (empty($line_items)) {
            return; // Nothing on this order for Filecheck
        }

        $id_lang = $this->context->language ? (int)$this->context->language->id : (int)Configuration::get('PS_LANG_DEFAULT');
        $state = new OrderState((int)$order->current_state, $id_lang);
        $status_name = $state->name ?: 'Paid';

        $customer = new Customer((int)$order->id_customer);
        $address = new Address((int)$order->id_address_delivery);
        $currency = new Currency((int)$order->id_currency);

        $payload = [
            'orderId' => (string)$order->id,
            // What the customer sees and types on the upload page.
            'orderName' => (string)$order->reference,
            'status' => $custom_status ?? $status_name,
            'currency' => $currency->iso_code,
            'total' => (float)$order->total_paid_tax_incl,
            'customer' => [
                'id' => (string)$customer->id,
                'name' => trim($customer->firstname . ' ' . $customer->lastname),
                'email' => $customer->email,
                'phone' => $address->phone ?: $address->phone_mobile,
            ],
            'items' => $line_items,
        ];

        if ($address->address1) {
            $payload['shippingAddress'] = [
                'name' => trim($address->firstname . ' ' . $address->lastname),
                'address1' => $address->address1,
                'address2' => $address->address2 ?: '',
                'city' => $address->city,
                'state' => State::getNameById($address->id_state) ?: '',
                'postcode' => $address->postcode,
                'country' => Country::getNameById($id_lang, $address->id_country),
                'company' => $address->company ?: '',
            ];
        }

        $synced = FilecheckAPIClient::instance()->syncOrder($order->id, $payload, $secret_key);
        if (!empty($jobs)) {
            FilecheckJob::markSynced($order->id);
        }

        // First sync of an order still waiting for artwork: send the customer
        // the upload link through the store's own mailer, once.
        if ($synced === true && $awaiting && $custom_status === null) {
            $this->sendUploadLinkEmail($order);
        }
    }

    // -------------------------------------------------------------
    // ARTWORK AFTER CHECKOUT: upload link
    // -------------------------------------------------------------

    protected static $uploadLinks = [];

    /** Does this order have a line that collects artwork after the order? (no API call) */
    protected function orderMayAwaitArtwork($order)
    {
        foreach ($order->getProducts() as $p) {
            $id = (int) $p['product_id'];
            if (FilecheckProduct::workflowId($id) && FilecheckProduct::artworkTiming($id) !== 'checkout') {
                return true;
            }
        }
        return false;
    }

    /** { url, status, ... } from Filecheck for an order still waiting for artwork, else null. */
    public function getUploadLink($order)
    {
        if (!Validate::isLoadedObject($order) || !$this->orderMayAwaitArtwork($order)) {
            return null;
        }
        $id = (int) $order->id;
        if (!array_key_exists($id, self::$uploadLinks)) {
            $link = FilecheckAPIClient::instance()->getUploadLink($id);
            self::$uploadLinks[$id] = ($link && ($link['status'] ?? '') !== 'complete') ? $link : null;
        }
        return self::$uploadLinks[$id];
    }

    protected function sendUploadLinkEmail($order)
    {
        if (!Configuration::get('FILECHECK_UPLOAD_EMAIL', null, null, null, 1)) {
            return;
        }
        $link = $this->getUploadLink($order);
        $customer = new Customer((int) $order->id_customer);
        if (!$link || !Validate::isEmail($customer->email)) {
            return;
        }
        // The template ships in English only: a language without one would make
        // Mail::Send refuse to send, so fall back to English (else the shop default).
        $id_lang = (int) $order->id_lang;
        $dir = _PS_MODULE_DIR_ . $this->name . '/mails/';
        if (!is_file($dir . Language::getIsoById($id_lang) . '/filecheck_upload.html')) {
            $id_lang = (int) Language::getIdByIso('en') ?: (int) Configuration::get('PS_LANG_DEFAULT');
        }
        Mail::Send(
            $id_lang,
            'filecheck_upload',
            $this->trans('Upload your artwork for order %s', [$order->reference], 'Modules.Filecheck.Shop'),
            [
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{order_name}' => $order->reference,
                '{filecheck_upload_link}' => $link['url'],
            ],
            $customer->email,
            trim($customer->firstname . ' ' . $customer->lastname),
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . $this->name . '/mails/',
            false,
            (int) $order->id_shop
        );
    }

    protected function renderUploadLink($order)
    {
        $link = $this->getUploadLink($order);
        if (!$link) {
            return '';
        }
        $this->context->smarty->assign(['fc_upload_url' => $link['url']]);
        return $this->display(__FILE__, 'views/templates/hook/upload_link.tpl');
    }

    public function hookDisplayOrderConfirmation($params)
    {
        return $this->renderUploadLink($params['order'] ?? null);
    }

    public function hookDisplayOrderDetail($params)
    {
        return $this->renderUploadLink($params['order'] ?? null);
    }

    /**
     * Lets a merchant put {filecheck_upload_link} in any store email template
     * that carries an order (order_conf, payment, ...). Empty when the order
     * waits for no artwork.
     */
    public function hookActionEmailSendBefore($params)
    {
        $vars = &$params['templateVars'];
        if (!is_array($vars) || isset($vars['{filecheck_upload_link}'])) {
            return;
        }
        $vars['{filecheck_upload_link}'] = '';
        $id_order = isset($vars['{id_order}']) ? (int) $vars['{id_order}'] : 0;
        if (!$id_order) {
            return;
        }
        $link = $this->getUploadLink(new Order($id_order));
        if ($link) {
            $vars['{filecheck_upload_link}'] = $link['url'];
        }
    }

    public function processOrderFiles($order)
    {
        if (FilecheckJob::isProcessed($order->id)) {
            return;
        }

        $secret_key = Configuration::get('FILECHECK_SECRET_KEY');
        if (empty($secret_key)) {
            return;
        }

        $jobs = FilecheckJob::getJobsForOrder($order->id);
        if (empty($jobs)) {
            return;
        }

        $processed_any = false;

        foreach ($jobs as $job) {
            $job_id = $job['job_id'];
            if (empty($job_id)) {
                continue;
            }

            $job_details = FilecheckAPIClient::instance()->getJob($job_id, $secret_key);
            if (!$job_details || isset($job_details['error'])) {
                continue;
            }

            $runs = $job_details['runs'] ?? [];
            if (empty($runs)) {
                continue;
            }

            $downloaded_files = [];

            foreach ($runs as $run) {
                $run_id = $run['id'] ?? '';
                if (empty($run_id)) {
                    continue;
                }

                // The signed URL of the file to fulfil (the corrected file,
                // else the upload). Missing while the run is still processing.
                $download_url = $run['downloadUrl'] ?? '';
                if (empty($download_url)) {
                    continue;
                }

                $filename = $run['name'] ?? '';
                if (empty($filename)) {
                    $ext = '.pdf';
                    if (isset($run['acceptKey']) && $run['acceptKey'] === 'raster') {
                        $ext = '.png';
                    }
                    $filename = $job_id . '-' . $run_id . $ext;
                }

                // Sanitize filename
                $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

                // Setup local directory
                $secure_dir = _PS_UPLOAD_DIR_ . 'filecheck-secure';
                if (!file_exists($secure_dir)) {
                    @mkdir($secure_dir, 0755, true);
                    @file_put_contents($secure_dir . '/.htaccess', "Deny from all\n");
                    @file_put_contents($secure_dir . '/index.php', "<?php // Silence\n");
                }

                // Run id keeps two same-named files in one order apart.
                $local_filename = time() . '_' . $run_id . '_' . $filename;
                $local_filepath = $secure_dir . '/' . $local_filename;

                // Stream secure download. No Authorization header: the URL is
                // presigned, and storage rejects a request that carries both.
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $download_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 300);

                $fp = fopen($local_filepath, 'w+');
                if (!$fp) {
                    curl_close($ch);
                    continue;
                }
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_exec($ch);

                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                fclose($fp);

                if ($http_code !== 200) {
                    if (file_exists($local_filepath)) {
                        @unlink($local_filepath);
                    }
                    continue;
                }

                $downloaded_files[] = [
                    'run_id' => $run_id,
                    'filename' => $filename,
                    'local_path' => $local_filepath,
                    'local_name' => $local_filename,
                ];
            }

            if (!empty($downloaded_files)) {
                FilecheckJob::updateDownloadedFiles($job['id_filecheck_job'], $downloaded_files);
                $processed_any = true;
            }
        }

        if ($processed_any) {
            FilecheckJob::markProcessed($order->id);
        }
    }

    /**
     * Display metabox on the backoffice order details page (PrestaShop 1.7.7+)
     */
    public function hookDisplayAdminOrder($params)
    {
        $id_order = (int) $params['id_order'];
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $jobs = FilecheckJob::getJobsForOrder($id_order);
        if (empty($jobs)) {
            return '';
        }

        $this->context->smarty->assign([
            'fc_order_id' => $id_order,
            'ajax_order_url' => $this->context->link->getAdminLink('AdminModules', true, [], [
                'configure' => $this->name,
                'action' => 'getOrderJobDetails',
            ]),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/order_summary.tpl');
    }

    /**
     * Extra settings for Product edit page in Admin
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int)$params['id_product'];
        $prod_config = FilecheckProduct::getForProduct($id_product);

        $workflows = FilecheckAPIClient::instance()->getWorkflows();
        $connectors = FilecheckAPIClient::instance()->getConnectors();

        $this->context->smarty->assign([
            'fc_prod_workflow_id' => $prod_config['workflow_id'] ?: 'none',
            'fc_prod_connector_id' => $prod_config['connector_id'] ?: '',
            'fc_prod_presentation' => $prod_config['presentation'] ?: '',
            'fc_prod_timing' => FilecheckProduct::getTiming($id_product),
            'fc_workflows' => $workflows,
            'fc_connectors' => $connectors,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product_extra.tpl');
    }

    public function hookActionProductUpdate($params)
    {
        $id_product = (int)$params['id_product'];
        if (!$id_product) {
            return;
        }

        if (Tools::isSubmit('filecheck_workflow_id')) {
            $workflow_id = Tools::getValue('filecheck_workflow_id');
            $connector_id = Tools::getValue('filecheck_connector_id');
            $presentation = Tools::getValue('filecheck_presentation');

            FilecheckProduct::saveForProduct($id_product, $workflow_id, $connector_id, $presentation);
            FilecheckProduct::saveTiming($id_product, Tools::getValue('filecheck_artwork_timing'));
        }
    }

    public function hookActionAdminControllerSetMedia()
    {
        if (Tools::getValue('controller') === 'AdminOrders' || Tools::getValue('configure') === $this->name) {
            $this->context->controller->addJS($this->getPathUri() . 'views/js/admin.js');
            $this->context->controller->addJS($this->getPathUri() . 'views/js/order-admin.js');
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/admin.css');

            $ajax_order_url = $this->context->link->getAdminLink('AdminModules', true, [], [
                'configure' => $this->name,
                'action' => 'getOrderJobDetails',
            ]);

            Media::addJsDef([
                'filecheck_order_admin' => [
                    'ajax_url' => $ajax_order_url,
                    'nonce' => '', // PrestaShop secure links use admin token inside link itself
                    'i18n' => [
                        'loading' => $this->l('Loading file details…'),
                        'error' => $this->l('Could not load file details.'),
                        'download' => $this->l('Download'),
                        'view' => $this->l('View Job on Filecheck'),
                        'noFiles' => $this->l('No processed files yet.'),
                    ]
                ]
            ]);
        }
    }

    /**
     * Backoffice AJAX requests dispatcher
     */
    public function ajaxProcessGetOrderJobDetails()
    {
        ob_end_clean();
        header('Content-Type: application/json');

        $id_order = (int) Tools::getValue('order_id');
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }

        $secret_key = Configuration::get('FILECHECK_SECRET_KEY');
        if (empty($secret_key)) {
            echo json_encode(['success' => false, 'message' => 'Secret key not configured']);
            exit;
        }

        $jobs = FilecheckJob::getJobsForOrder($id_order);
        $items = [];

        foreach ($jobs as $job) {
            $job_id = $job['job_id'];
            if (empty($job_id)) {
                continue;
            }

            $summary = FilecheckAPIClient::instance()->getJobSummary($job_id, $secret_key);

            // Find product name
            $p_name = 'Product #' . $job['id_product'];
            $products = $order->getProducts();
            foreach ($products as $p) {
                if ((int)$p['product_id'] === (int)$job['id_product']) {
                    $p_name = $p['product_name'];
                    break;
                }
            }

            // Check details of downloaded files if present
            $local_download_url = '';
            $files_processed = [];
            
            $db_downloaded = [];
            if (!empty($job['downloaded_files'])) {
                $db_downloaded = json_decode($job['downloaded_files'], true) ?: [];
            }

            $raw_files = !empty($summary) && isset($summary['files']) ? $summary['files'] : [];
            $mapped_files = [];

            foreach ($raw_files as $rf) {
                $download_url = $rf['downloadUrl'];
                
                // If there's a secure offline download available, generate a link to download from PrestaShop admin controller
                foreach ($db_downloaded as $idx => $dd) {
                    if ($dd['run_id'] === $rf['runId']) {
                        $download_url = $this->context->link->getAdminLink('AdminModules', true, [], [
                            'configure' => $this->name,
                            'action' => 'downloadSecureFile',
                            'job_id' => $job['id_filecheck_job'],
                            'file_index' => $idx,
                        ]);
                        break;
                    }
                }

                $mapped_files[] = [
                    'runId' => $rf['runId'],
                    'name' => $rf['name'],
                    'outcome' => $rf['outcome'],
                    'status' => $rf['status'],
                    'hasOutput' => $rf['hasOutput'],
                    'downloadUrl' => $download_url,
                    'proofs' => $rf['proofs'],
                ];
            }

            $items[] = [
                'itemId' => $job['id_filecheck_job'],
                'itemName' => $p_name,
                'jobId' => $job_id,
                'adminUrl' => 'https://admin.filecheck.io/orders/' . rawurlencode((string)$id_order) . '/' . rawurlencode($job_id),
                'status' => $summary['status'] ?? '',
                'files' => $mapped_files,
                'error' => is_string($summary) ? $summary : null
            ];
        }

        echo json_encode(['success' => true, 'data' => ['items' => $items]]);
        exit;
    }

    /**
     * Backoffice trigger to download securely stored local print PDFs
     */
    public function ajaxProcessDownloadSecureFile()
    {
        $id_job = (int) Tools::getValue('job_id');
        $file_index = (int) Tools::getValue('file_index');

        $job = FilecheckJob::getJobById($id_job);
        if (!$job) {
            die('Job not found.');
        }

        $files = json_decode($job['downloaded_files'], true) ?: [];
        if (!isset($files[$file_index])) {
            die('File reference not found.');
        }

        $file = $files[$file_index];
        $filepath = $file['local_path'];

        if (!file_exists($filepath)) {
            die('Physical file does not exist on disk.');
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file['filename']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));

        readfile($filepath);
        exit;
    }
}
