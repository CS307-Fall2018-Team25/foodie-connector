<?php

namespace App\Facades;

use App\Exceptions\ApiException;
use App\Exceptions\MapsException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;

class Maps
{
    /**
     * Make requests on Google Maps APIs
     *
     * @param string $method
     * @param string $uri
     * @param array $query [optional]
     * @return array
     *
     * @throws \App\Exceptions\MapsException
     * @throws \Exception
     */
    protected static function request(string $method, string $uri, array $query = [])
    {
        $client = new Client([
            'base_uri' => config('services.google_maps.base_uri'),
        ]);
        $query['key'] = config('services.google_maps.secret');
        try {
            $response = $client->request($method, $uri, [
                'query' => $query,
            ]);
        } catch (GuzzleException $exception) {
            throw new MapsException($exception->getMessage(), $exception->getCode(), $exception);
        }
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get address from place ID
     *
     * @param string $placeId
     * @return array
     *
     * @throws \App\Exceptions\ApiException
     * @throws \App\Exceptions\MapsException
     * @throws \Exception
     */
    public static function reverseGeoCodingByPlaceID(string $placeId)
    {
        try {
            $response = self::request('GET', 'geocode/json', [
                'place_id' => $placeId,
            ]);
        } catch (MapsException $exception) {
            if ($exception->getCode() === 400) {
                throw ApiException::invalidPlaceId();
            }
            throw $exception;
        }
        if ($response['status'] !== 'OK') {
            throw new MapsException($response['status']);
        }
        return $response['results'];
    }

    /**
     * Get latitude and longitude from place ID
     *
     * @param string $placeId
     * @return array
     *
     * @throws \App\Exceptions\ApiException
     * @throws \App\Exceptions\MapsException
     * @throws \Exception
     */
    public static function latLngByPlaceID(string $placeId)
    {
        $geoCoding = self::reverseGeoCodingByPlaceID($placeId);
        return [
            'lat' => $geoCoding[0]['geometry']['location']['lat'],
            'lng' => $geoCoding[0]['geometry']['location']['lng'],
        ];
    }
}