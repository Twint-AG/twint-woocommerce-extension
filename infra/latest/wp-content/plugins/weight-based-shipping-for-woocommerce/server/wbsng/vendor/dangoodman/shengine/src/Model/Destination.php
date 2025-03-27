<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Model;

use InvalidArgumentException;


class Destination
{
    public function __construct($country, $state = null, $postalCode = null, $city = null, Address $address = null)
    {
        $country = (string)$country;
        if ($country === '') {
            throw new InvalidArgumentException("Destintaion cannot be created without a country specified");
        }

        $this->country = $country;
        $this->state = (string)$state === '' ? null : (string)$state;
        $this->postalCode = (string)$postalCode === '' ? null : (string)$postalCode;
        $this->city = $city;
        $this->address = $address;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getPostalCode()
    {
        return $this->postalCode;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getAddress()
    {
        return $this->address;
    }

    private $country;
    private $state;
    private $postalCode;
    private $city;
    private $address;
}