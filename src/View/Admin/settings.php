<?php ?>
<div class="wrap">
    <div id="notice-admin-success" class="tw-hidden notice notice-success">
        <p> <?php echo esc_html__('Certificate validation successful', 'twint-woocommerce-extension') ?></p>
    </div>
    <div id="notice-admin-error" class="tw-hidden notice notice-error is-dismissible"></div>

    <form method="post" action="" novalidate="novalidate" enctype="multipart/form-data" autocomplete="off">
        <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
            <?php
            //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The HTML is safe and intended for raw output.
            echo $this->getTabHtml($tabs)
?>
        </nav>

        <div class="tab-content">
            <?php
//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The HTML is safe and intended for raw output.
echo $this->getTabContent()
?>
        </div>
    </form>
</div>
