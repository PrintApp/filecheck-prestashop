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

class FilecheckAPIClient
{
    protected static $_instance = null;
    protected $api_url = 'https://api.filecheck.io';

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        $override = Configuration::get('FILECHECK_API_URL');
        if (!empty($override)) {
            $this->api_url = rtrim($override, '/');
        }
    }

    public function getApiUrl()
    {
        return $this->api_url;
    }

    protected function getHeaders($secret_key)
    {
        return [
            'Authorization: Bearer ' . $secret_key,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }

    /**
     * Executes a curl request
     */
    protected function request($url, $method = 'GET', $body = null, $secret_key = '')
    {
        if (empty($secret_key)) {
            $secret_key = Configuration::get('FILECHECK_SECRET_KEY');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders($secret_key));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return new PrestaShopException('API Connection error: ' . $error);
        }

        return [
            'code' => $http_code,
            'body' => json_decode($response, true) ?: $response,
            'raw'  => $response
        ];
    }

    public function verifyKeys($publishable_key, $secret_key)
    {
        if (empty($secret_key)) {
            return new PrestaShopException('Secret key is required.');
        }

        $url = $this->api_url . '/workflows/';
        $response = $this->request($url, 'GET', null, $secret_key);

        if ($response instanceof PrestaShopException) {
            return $response;
        }

        if ($response['code'] === 200) {
            return true;
        }

        if ($response['code'] === 401 || $response['code'] === 403) {
            return new PrestaShopException('Authentication failed. Please check your keys.');
        }

        $message = isset($response['body']['message']) ? $response['body']['message'] : 'API returned HTTP code ' . $response['code'];
        return new PrestaShopException($message);
    }

    public function getWorkflows($secret_key = '')
    {
        if (empty($secret_key)) {
            $secret_key = Configuration::get('FILECHECK_SECRET_KEY');
        }

        if (empty($secret_key)) {
            return [];
        }

        $url = $this->api_url . '/workflows/';
        $response = $this->request($url, 'GET', null, $secret_key);

        if ($response instanceof PrestaShopException || $response['code'] !== 200) {
            return [];
        }

        $body = $response['body'];
        if (isset($body['workflows']) && is_array($body['workflows'])) {
            return $body['workflows'];
        }
        if (isset($body['rules']) && is_array($body['rules'])) {
            return $body['rules'];
        }

        return is_array($body) ? $body : [];
    }

    public function getConnectors($secret_key = '')
    {
        if (empty($secret_key)) {
            $secret_key = Configuration::get('FILECHECK_SECRET_KEY');
        }

        if (empty($secret_key)) {
            return [];
        }

        $url = $this->api_url . '/connectors/';
        $response = $this->request($url, 'GET', null, $secret_key);

        if ($response instanceof PrestaShopException || $response['code'] !== 200) {
            return [];
        }

        $body = $response['body'];
        if (isset($body['connectors']) && is_array($body['connectors'])) {
            return $body['connectors'];
        }

        return is_array($body) ? $body : [];
    }

    public function syncOrder($order_id, $payload, $secret_key = '')
    {
        if (empty($secret_key)) {
            $secret_key = Configuration::get('FILECHECK_SECRET_KEY');
        }

        if (empty($secret_key)) {
            return new PrestaShopException('Secret key is missing.');
        }

        $url = $this->api_url . '/orders/' . urlencode($order_id);
        $response = $this->request($url, 'POST', $payload, $secret_key);

        if ($response instanceof PrestaShopException) {
            return $response;
        }

        if ($response['code'] < 200 || $response['code'] >= 300) {
            $message = isset($response['body']['message']) ? $response['body']['message'] : 'API returned HTTP code ' . $response['code'];
            return new PrestaShopException($message);
        }

        return true;
    }

    public function getJob($job_id, $secret_key = '')
    {
        if (empty($secret_key)) {
            $secret_key = Configuration::get('FILECHECK_SECRET_KEY');
        }

        if (empty($secret_key)) {
            return new PrestaShopException('Secret key is missing.');
        }

        $url = $this->api_url . '/jobs/' . urlencode($job_id) . '?expand=runs';
        $response = $this->request($url, 'GET', null, $secret_key);

        if ($response instanceof PrestaShopException) {
            return ['error' => $response->getMessage()];
        }

        if ($response['code'] !== 200) {
            return ['error' => 'API returned HTTP code ' . $response['code']];
        }

        return $response['body'];
    }

    public function getJobSummary($job_id, $secret_key = '')
    {
        $job = $this->getJob($job_id, $secret_key);
        if (isset($job['error'])) {
            return $job['error'];
        }

        $files = [];
        $runs = isset($job['runs']) && is_array($job['runs']) ? $job['runs'] : [];

        foreach ($runs as $run) {
            $run_id = isset($run['id']) ? $run['id'] : '';
            if (empty($run_id)) {
                continue;
            }

            $name = isset($run['name']) ? $run['name'] : $run_id;

            $proofs = [];
            if (isset($run['proofs']) && is_array($run['proofs'])) {
                foreach ($run['proofs'] as $proof) {
                    if (!empty($proof['url'])) {
                        $proofs[] = [
                            'url' => $proof['url'],
                        ];
                    }
                }
            }

            $files[] = [
                'runId'       => $run_id,
                'name'        => $name,
                'outcome'     => isset($run['outcome']) ? $run['outcome'] : null,
                'status'      => isset($run['status']) ? $run['status'] : '',
                'hasOutput'   => !empty($run['hasOutput']),
                'downloadUrl' => isset($run['downloadUrl']) ? $run['downloadUrl'] : '',
                'proofs'      => $proofs,
            ];
        }

        return [
            'jobId'  => $job_id,
            'status' => isset($job['status']) ? $job['status'] : '',
            'files'  => $files,
        ];
    }
}
