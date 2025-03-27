<?php
declare(strict_types=1);

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces;

use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions\DeleteInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions\FetchInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions\FetchPrefixInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions\SetInterface;

/**
 * Define mandatory methods to implement when using this package
 */
interface OptionsInterface extends  FetchPrefixInterface, DeleteInterface, FetchInterface, SetInterface {}