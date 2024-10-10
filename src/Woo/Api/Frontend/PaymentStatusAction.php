<?php

declare(strict_types=1);

namespace Twint\Woo\Api\Frontend;

use Exception;
use Throwable;
use Twint\Woo\Api\BaseAction;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
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
        private readonly Lazy|MonitorService      $service,
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
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $this->initCartIfNeed();

        $pairingId = $request->get_param('pairingId');

        $pairing = $this->getRepository()->get($pairingId);

        if (!$pairing instanceof Pairing) {
            throw new Exception('The pairing for the the order does not exist.');
        }

        $cliSupport = get_option(TwintConstant::CONFIG_CLI_SUPPORT_OPTION) === 'Yes';
        $status = $cliSupport ? $this->getService()->status($pairing) : $this->getService()->monitor($pairing);

        $response = $status->toArray();

        if ($status->paid()) {
            WC()->cart->empty_cart();
            $order = wc_get_order($pairing->getWcOrderId());

            $response['extra'] = [
                'redirect' => $order->get_checkout_order_received_url(),
            ];
        }

        return new WP_REST_Response($response, 200);
    }
}
