{**
 * Artwork after checkout: link to the order on the Filecheck upload page.
 *}
<div class="alert alert-info filecheck-upload-artwork" role="alert">
    <p>{l s='Some items on this order still need your artwork. Upload your files any time, and we will check them before we print.' d='Modules.Filecheck.Shop'}</p>
    <a class="btn btn-primary" href="{$fc_upload_url|escape:'html':'UTF-8'}">{l s='Upload artwork' d='Modules.Filecheck.Shop'}</a>
</div>
