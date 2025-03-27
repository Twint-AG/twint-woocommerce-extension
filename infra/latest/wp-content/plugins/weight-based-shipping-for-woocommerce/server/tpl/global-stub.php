<!--suppress CssUnusedSymbol -->
<style>
    #mainform .form-table, #mainform p.submit {
        display: none;
    }

    .wbs-global-method-notice {
        max-width: 750px;
        font-size: 15px;
    }

    .wbs-global-method-button {
        font-size: 15px !important;
    }
</style>

<br>

<p class="wbs-global-method-notice">
    Here you define a global shipping method – a method that is not tied to a specific shipping zone.
</p>
<p class="wbs-global-method-notice">
    You can go with the global or zone-specific methods or both, but if unsure, start with shipping zones.
</p>
<p class="wbs-global-method-notice">
    Find more details about how shipping works in WooCommerce in the following
    <a target="_blank" href="<?= esc_html('https://docs.woocommerce.com/document/setting-up-shipping-zones/') ?>">
        documentation article
    </a>.
</p>

<br>

<a class="button-primary wbs-global-method-button" href="<?= esc_html(admin_url('admin.php?page=wc-settings&tab=shipping')) ?>">
    Go to shipping zones
</a>
&nbsp;&nbsp;&nbsp;
<a class="button wbs-global-method-button" id="wbs_proceed_with_global" href="#">
    Set up global shipping rules
</a>

<script>
    ($ => {
        $('#wbs_proceed_with_global').attr('href', document.location.href + '&wbs_global');
    })(jQuery)
</script>