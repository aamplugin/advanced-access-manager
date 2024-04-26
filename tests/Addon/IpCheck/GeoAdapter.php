<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Addon\IpCheck;

use AAM\AddOn\IPCheck\Adapter\Base;

/**
 * Undocumented class
 */
class GeoAdapter extends Base
{

    /**
     * Property map
     */
    const PROPERTY_MAP = array(
        'country_name'   => 'country_name',
        'country_code'   => 'country_code',
        'continent_code' => 'continent_code',
        'region_name'    => 'region',
        'zip_code'       => 'postal',
        'city'           => 'city',
        'latitude'       => 'latitude',
        'longitude'      => 'longitude'
    );

    /**
     * Undocumented function
     *
     * @param [type] $ip
     * @return void
     */
    protected function lookupIp($ip)
    {
        return [
            "ip" => "98.26.4.6",
            "network" => "98.26.0.0/20",
            "version" => "IPv4",
            "city" => "Marion",
            "region" => "South Carolina",
            "region_code" => "SC",
            "country" => "US",
            "country_name" => "United States",
            "country_code" => "US",
            "country_code_iso3" => "USA",
            "country_capital" => "Washington",
            "country_tld" => ".us",
            "continent_code" => "NA",
            "in_eu" => false,
            "postal" => "29571",
            "latitude" => 34.156,
            "longitude" => -79.3906,
            "timezone" => "America/New_York",
            "utc_offset" => "-0400",
            "country_calling_code" => "+1",
            "currency" => "USD",
            "currency_name" => "Dollar",
            "languages" => "en-US,es-US,haw,fr",
            "country_area" => 9629091.0,
            "country_population" => 327167434,
            "asn" => "AS11426",
            "org" => "TWC-11426-CAROLINAS"
        ];
    }

}