# Filecheck PrestaShop 8 Module

This module integrates the **Filecheck Element** preflight widget directly into PrestaShop product pages. It allows customers to upload and validate print-ready assets before proceeding to add items to their cart.

---

## Architecture Overview

The PrestaShop module is structured as follows:

- **filecheck.php**  
  The main entry point of the module. Configures PrestaShop admin tabs, installs and uninstalls DB tables, registers modern hook listeners, and dispatches back-office AJAX calls.

- **classes/FilecheckAPIClient.php**  
  Server-side PHP wrapper to connect with the Filecheck REST API. Handles authenticating keys, listing active workflows, registering order details, and fetching job summaries/run outputs.

- **classes/FilecheckProduct.php**  
  Handles active product database persistence. Configures custom workflows, connectors, or presentation mode overrides per-product.

- **classes/FilecheckJob.php**  
  Active job mapping layer. Connects guests' uploaded Filecheck job IDs with their PrestaShop cart items and, upon successful checkout, links those files to finalized order lines.

- **controllers/front/ajax.php**  
  Frontend AJAX controller that allows the client to push job IDs to session cookies, ensuring robust session survival through AJAX-based cart checkouts.

- **views/templates/admin/settings.tpl**  
  Sleek back-office configuration page allowing merchants to save credentials, list workflows, and run connection checks.

- **views/templates/admin/product_extra.tpl**  
  Implements the extra product settings tab (Extra Fields) inside standard PrestaShop product editors.

- **views/templates/admin/order_summary.tpl**  
  Injected template grid displaying uploaded runs, status badges, proof thumbnails, and direct download links under the PrestaShop order edit page index.

- **views/templates/hook/product_widget.tpl**  
  Frontend template injecting the widget placeholder container and hidden inputs directly next to the add-to-cart action.

---

## Installation & Setup

1. Compress the root directory of this module into a ZIP file named `filecheck.zip`.
2. Go to **Modules** > **Module Manager** in your PrestaShop 8 back office.
3. Click **Upload a module** and select your `filecheck.zip` file.
4. After installation, click **Configure**.
5. Input your **Publishable Key** and **Secret Key**. You can click **Test Connection** to verify keys immediately.
6. Choose your **Default Workflow** from the synchronised dropdown and click **Save Settings**.

---

## How It Works

### 1. Initialization
When a user visits a product page with an assigned Filecheck workflow, the module enqueues the per-tenant CDN client library:
`https://cdn.filecheck.io/element/{publishable_key}/filecheck.js`

And injects a target container element:
`<div id="fc-slot-{product_id}"></div>`

### 2. Client Mounting
`views/js/frontend.js` awaits the presence of the `Filecheck` library, constructs the required mounting configurations, and calls `window.Filecheck.mount(config)`.

Passes `.add-to-cart` as the `cartButtonSelector` configuration option, instructing the Element itself to control the Add to Cart button state, disabling it dynamically until valid files are provided.

### 3. Session Resilience & Redundancy
To ensure compatibility with AJAX-based cart templates, the module uses a dual-gating pattern:
- **Form POST Gating:** Captures the `jobId` inside a hidden input field named `filecheck_job_id`.
- **PrestaShop Session Gating:** Simultaneously pushes the current `jobId` to the `ajax` front controller, which saves it inside `$this->context->cookie` under the key `fc_job_{product_id}`.

### 4. Order Synchronization & Fulfillment
Upon standard or quick checkout:
- The `job_id` is captured and committed to `ps_filecheck_jobs`.
- Upon order validation, details are synchronized securely with Filecheck to record origin details inside the merchant back-office.
- When an order transitions to paid or processing statuses, high-res outputs are safely downloaded to a shielded filesystem index (`/upload/filecheck-secure/`) protected by `.htaccess` rules.
- Merchants can inspect and download preflighted outputs directly within the PrestaShop Order details page.
