<?php

namespace AppBundle\Controller;

use CommerceGuys\Guzzle\Oauth2\GrantType\ClientCredentials;
use CommerceGuys\Guzzle\Oauth2\Oauth2Subscriber;
use GuzzleHttp\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    const ACCESS_TOKEN_CACHE_KEY_PREFIX = 'oauth_cache_key';

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        $base_url = 'http://time-api.local';

        // Create a Guzzle client
        $oauth2Client = new Client(['base_url' => $base_url]);

        $config = [
            'client_id' => '1_2es0ucqqq5es4kcogow84wss8csskgk4gsso0gc444gk8wo8c4',
            'client_secret' => '5ur5owi3xfgg8cowkkg4socg8g8w4csgk8gowck40g8s04oo84',
            'token_url' => '/oauth/v2/token'
        ];

        $token = new ClientCredentials($oauth2Client, $config);

        $oauth2 = new Oauth2Subscriber($token);

        // Create an OAuth2 client
        $client = new Client([
            'defaults' => [
                'auth' => 'oauth2',
                'subscribers' => [$oauth2],
            ],
        ]);

        // Request our api with the OAuth2 client
        $response = $client->get('http://time-api.local/api/time');

        $result = json_decode($response->getBody(), true);

        return new Response(sprintf(
            "Date from the API <br/> Date: %s <br/> Time: %s",
            $result['date'],
            $result['time']
        ));
    }

    /**
     * @Route("/cache", name="homepage-cache")
     */
    public function cacheAction()
    {
        $base_url = 'http://time-api.local';

        $oauth2Client = new Client(['base_url' => $base_url]);

        $config = [
            'client_id' => '1_2es0ucqqq5es4kcogow84wss8csskgk4gsso0gc444gk8wo8c4',
            'client_secret' => '5ur5owi3xfgg8cowkkg4socg8g8w4csgk8gowck40g8s04oo84',
            'token_url' => '/oauth/v2/token'
        ];

        $memcached = new \Memcached();
        $memcached->addServer('localhost', 11211);

        $token = new ClientCredentials($oauth2Client, $config);
        $oauth2 = new Oauth2Subscriber($token);

        // Check if we have the cache key
        if (false !== $cachedAccessToken = $memcached->get(self::ACCESS_TOKEN_CACHE_KEY_PREFIX)) {
            $oauth2->setAccessToken($cachedAccessToken);
        }

        // Update the cache token with the access token
        $memcached->set(self::ACCESS_TOKEN_CACHE_KEY_PREFIX, $oauth2->getAccessToken());

        $client = new Client([
            'defaults' => [
                'auth' => 'oauth2',
                'subscribers' => [$oauth2],
            ],
        ]);

        $response = $client->get('http://time-api.local/api/time');

        $result = json_decode($response->getBody(), true);

        return new Response(sprintf(
            "Date from the API <br/> Date: %s <br/> Time: %s",
            $result['date'],
            $result['time']
        ));
    }
}
