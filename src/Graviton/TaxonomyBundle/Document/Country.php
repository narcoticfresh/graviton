<?php

namespace Graviton\TaxonomyBundle\Document;

/**
 * document for representing a country
 *
 * @category GravitonTaxonomyBundle
 * @package  Graviton
 * @author   Lucas Bickel <lucas.bickel@swisscom.com>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.com
 */
class Country
{
    /**
     * @var MongoId $id document/country id
     */
    protected $id;

    /**
     * @var String $name Country Name
     */
    protected $name;

    /**
     * @var String $isoCode ISO country code
     */
    protected $isoCode;

    /**
     * @var String $capitalCity capital city of country
     */
    protected $capitalCity;

    /**
     * @var String $longitude Longitude of country
     */
    protected $longitude;

    /**
     * @var String $latitude Latitude of country
     */
    protected $latitude;

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name
     *
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * get ISO code of country
     *
     * @return String
     */
    public function getIsoCode()
    {
        return $this->isoCode;
    }

    /**
     * get name of capital city
     *
     * @return String
     */
    public function getCapitalCity()
    {
        return $this->capitalCity;
    }

    /**
     * get longitude
     *
     * @return String
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * get latitude
     *
     * @return String
     */
    public function getLatitude()
    {
        return $this->latitude;
    }
}