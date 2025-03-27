<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */
/** @var ?\Gzp\WbsNg\Model\Calc\Solution $solution */
/** @var ?string $error */
// @formatter:off
?>

<?php if ($error): ?>
                <details>
                    <summary>Shipping breakdown read failed.</summary>
                    <pre style="font-size: small"><?= esc_html($error) ?></pre>
                </details>
<?php endif; ?>

<?php if (!$solution) return; ?>

                <div class="wbsng-shipments wbsng-shipments--brief">
<?php foreach ($solution->shipments as $shipment): ?>
                    <div class="wbsng-shipment">
                        <div class="wbsng-shipment-header">
                            <span class="wbsng-shipment-name"><?= esc_html($shipment->title) ?></span>
                            <span class="wbsng-shipment-price"><?= wc_price($shipment->price->__toString()) ?></span>
                        </div>
                        <div class="wbsng-shipment-detail">
                            <ul class="wbsng-shipment-products">
    <?php
    /** @var \Gzp\WbsNg\Model\Order\Item $item */
    foreach ($shipment->bundle as $i => $item):
        $last = $i === $shipment->bundle->count() - 1;
    ?>
                                <li class="wbsng-shipment-product"><!--
                                    --><span class="wbsng-shipment-product-name"><?= esc_html($item->name) ?></span><!--
                                    --><span class="wbsng-shipment-product-quantity"><?= esc_html((string)$item->quantity) ?></span><!--
                                    --><?php if (!$last): ?><span class="wbsng-shipment-product-delim">, </span><?php endif; ?>
                                </li>
    <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>