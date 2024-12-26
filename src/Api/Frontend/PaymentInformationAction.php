<?php

declare(strict_types=1);

namespace Twint\Woo\Api\Frontend;

use Exception;
use Throwable;
use Twint\Woo\Api\BaseAction;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\MonitorService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @method PairingRepository getRepository()
 * @method MonitorService getService()
 */
class PaymentInformationAction extends BaseAction
{
    use LazyLoadTrait;
    use CartInitTrait;

    protected static array $lazyLoads = ['repository'];

    public function __construct(
        private Lazy|PairingRepository $repository,
    ) {
        $this->registerHooks();
    }

    protected function registerHooks(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route('twint/v1', '/payment/information', [
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
        $pairingId = $request->get_param('pairingId');

        if (strlen($pairingId) !== 36) {
            return new WP_REST_Response([
                'message' => 'Invalid input',
            ], 400);
        }

        $pairing = $this->getRepository()->get($pairingId);

        if (!$pairing instanceof Pairing) {
            throw new Exception('The pairing for the the order does not exist.');
        }

        return new WP_REST_Response([
            'id' => $pairing->getId(),
            'token' => $pairing->getToken(),
            'amount' => wc_price($pairing->getAmount()),
            'finished' => $pairing->isFinished(),
        ], 200);
    }
}
