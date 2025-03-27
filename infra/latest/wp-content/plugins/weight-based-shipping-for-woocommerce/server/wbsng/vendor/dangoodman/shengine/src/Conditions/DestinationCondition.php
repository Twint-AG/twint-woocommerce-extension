<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions;

use GzpWbsNgVendors\Dgm\Arrays\Arrays;
use GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\AbstractCondition;
use Dgm\Shengine\Model\Destination;


class DestinationCondition extends AbstractCondition
{
    const POSTAL_CODE_CONSTRAINT_DELIMITER = '/zip:';
    const POSTAL_CODE_DELIMITER = ',';
    const POSTAL_CODE_RANGE_DELIMITER = '...';
    const COUNTRY_STATE_DELIMITER = ':';


    public function __construct($constraints)
    {
        $this->constraints = $constraints;
    }

    public function isSatisfiedBy($destination)
    {
        /** @var Destination $destination */
        if (!isset($destination)) {
            return false;
        }

        $country = $destination->getCountry();
        $state = $destination->getState();

        if (self::isCountryOrStateListed($country, $state, $this->constraints)) {
            return true;
        }

        if ($postcode = $destination->getPostalCode()) {
            foreach ($this->constraints as $constraint) {
                foreach (self::extractPostalCodeConstraints($constraint, $country, $state) as $postalCodeConstraint) {
                    if (self::isPostalCodeInRange($postcode, $postalCodeConstraint) ||
                        self::isPostalCodeMatchingPattern($postcode, $postalCodeConstraint)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private $constraints;

    private static function isCountryOrStateListed($country, $state, $constraints)
    {
        if ($country && in_array($country, $constraints, true)) {
            return true;
        }

        if ($country && $state &&
            in_array($country.self::COUNTRY_STATE_DELIMITER.$state, $constraints, true)) {
            return true;
        }

        return false;
    }

    private static function isPostalCodeInRange($code, $constraint)
    {
        if (count($range = explode(self::POSTAL_CODE_RANGE_DELIMITER, $constraint, 2)) > 1) {
            return
                self::comparePostalCodes($code, trim($range[0])) >= 0 &&
                self::comparePostalCodes($code, trim($range[1])) <= 0;
        }

        return false;
    }

    private static function comparePostalCodes($a, $b) {
        return strnatcasecmp($a, $b);
    }

    private static function isPostalCodeMatchingPattern($code, $constraint)
    {
        if (self::comparePostalCodes($code, $constraint) == 0) {
            return true;
        }

        if (strpos($constraint, '*') !== false) {
            $regex = '/^'.str_replace('\\*', '.+', preg_quote($constraint, '/')).'$/i';
            if (preg_match($regex, $code)) {
                return true;
            }
        }

        return false;
    }

    private static function extractPostalCodeConstraints($constraint, $country, $state)
    {
        $parts = explode(self::POSTAL_CODE_CONSTRAINT_DELIMITER, $constraint, 2);
        if (count($parts) == 2 && self::isCountryOrStateListed($country, $state, array(reset($parts)))) {
            return Arrays::map(explode(self::POSTAL_CODE_DELIMITER, end($parts)), 'trim');
        }

        return array();
    }
}