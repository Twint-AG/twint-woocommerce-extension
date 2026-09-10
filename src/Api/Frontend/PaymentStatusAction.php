<?php

declare(strict_types=1);

namespace Twint\Woo\Api\Frontend;

use Exception;
use Throwable;
use Twint\Woo\Api\BaseAction;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Model\Monitor\MonitoringStatus;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\MonitorService;
use WC_Logger_Interface;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @method PairingRepository getRepository()
 * @method MonitorService getService()
 */
class PaymentStatusAction extends BaseAction
{
    use LazyLoadTrait;
    use CartInitTrait;

    protected static array $lazyLoads = ['repository', 'service'];

    public function __construct(
        private Lazy|PairingRepository       $repository,
        private Lazy|MonitorService          $service,
        private readonly WC_Logger_Interface $logger
    ) {
        $this->registerHooks();
    }

    protected function registerHooks(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route('twint/v1', '/payment/status', [
                'methods' => 'POST',
                'callback' => [$this, 'handle'],
                'permission_callback' => '__return_true',
            ]);
        });

        $this->allowPublicAccessIfRouteMatches('/twint/v1/payment/status');
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $this->initCartIfNeed();

        $pairingId = $request->get_param('pairingId');

        if (strlen($pairingId) !== 36) {
            return new WP_REST_Response([
                'message' => 'Invalid input',
            ], 400);
        }

        $pairing = $this->getRepository()->get($pairingId);

        if (!$pairing instanceof Pairing) {
            throw new Exception('The pairing for the order does not exist.');
        }

        try {
            $status = $this->getService()->monitor($pairing);
        } catch (Throwable $e) {
            $this->logger->error(
                'TWINT PaymentStatusAction::handle: monitor failed ' . $e->getMessage(),
                [
                    'source' => 'twint-woocommerce-extension',
                    'wc_order_id' => $pairing->getWcOrderId(),
                ]
            );

            // Do not surface a transient failure as HTTP 500. The client keeps
            // polling and the pairing settles on a later poll (or via the cron).
            return new WP_REST_Response([
                'finish' => false,
                'status' => MonitoringStatus::STATUS_IN_PROGRESS,
                'extra' => [],
            ], 200);
        }

        $response = $status->toArray();

        if ($status->paid()) {
            WC()->cart->empty_cart();
            $order = wc_get_order($pairing->getWcOrderId());

            $response['extra'] = [
                'redirect' => $order->get_checkout_order_received_url(),
            ];
        }

        if ($status->isFailed()) {
            $response['extra'] = [
                'success' => false,
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- need to match on translated value from core.
                'message' => __('Error processing checkout. Please try again.', 'woocommerce'),
            ];
        }

        return new WP_REST_Response($response, 200);
    }
}
