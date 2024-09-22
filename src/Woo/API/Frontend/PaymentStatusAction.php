<?php

declare(strict_types=1);

namespace Twint\Woo\Api\Frontend;

use Exception;
use Throwable;
use Twint\Woo\Api\BaseAction;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\MonitorService;
use WC_Logger_Interface;
use WP_REST_Request;
use WP_REST_Response;

class PaymentStatusAction extends BaseAction
{
    use CartInitTrait; // 10 seconds

    public function __construct(
        private readonly PairingRepository $pairingRepository,
        private readonly MonitorService $service,
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

        $pairing = $this->pairingRepository->get($pairingId);
        if (!$pairing instanceof Pairing) {
            throw new Exception('The pairing for the the order does not exist.');
        }

        $status = $this->service->status($pairing);
        $response = $status->toArray();

        if($status->paid()){
            $order = wc_get_order($pairing->getWcOrderId());

            ob_start();
            wc_get_template( 'checkout/thankyou.php', array( 'order' => $order ) );

            $html = ob_get_clean();

            $response['extra']['thank-you'] = $html;
        }

        return new WP_REST_Response($response, 200);
    }
}
