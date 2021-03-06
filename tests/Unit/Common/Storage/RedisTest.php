<?php

/**
 * @author     David Desberg <david@daviddesberg.com>
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace OAuth\Unit\Common\Storage;

use OAuth\Common\Storage\Redis;
use OAuth\OAuth2\Token\StdOAuth2Token;
use PHPUnit\Framework\TestCase;
use Predis\Client as Predis;

class RedisTest extends TestCase
{
    protected $storage;

    protected function setUp(): void
    {
        // connect to a redis daemon
        $predis = new Predis([
            'host' => $_ENV['redis_host'],
            'port' => $_ENV['redis_port'],
        ]);

        // set it
        $this->storage = new Redis($predis, 'test_user_token', 'test_user_state');

        try {
            $predis->connect();
        } catch (\Predis\Connection\ConnectionException $e) {
            self::markTestSkipped('No redis instance available: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        // delete
        $this->storage->clearAllTokens();

        // close connection
        $this->storage->getRedis()->quit();
    }

    /**
     * Check that the token gets properly stored.
     */
    public function testStorage(): void
    {
        // arrange
        $service_1 = 'Facebook';
        $service_2 = 'Foursquare';

        $token_1 = new StdOAuth2Token('access_1', 'refresh_1', StdOAuth2Token::EOL_NEVER_EXPIRES, ['extra' => 'param']);
        $token_2 = new StdOAuth2Token('access_2', 'refresh_2', StdOAuth2Token::EOL_NEVER_EXPIRES, ['extra' => 'param']);

        // act
        $this->storage->storeAccessToken($service_1, $token_1);
        $this->storage->storeAccessToken($service_2, $token_2);

        // assert
        $extraParams = $this->storage->retrieveAccessToken($service_1)->getExtraParams();
        self::assertEquals('param', $extraParams['extra']);
        self::assertEquals($token_1, $this->storage->retrieveAccessToken($service_1));
        self::assertEquals($token_2, $this->storage->retrieveAccessToken($service_2));
    }

    /**
     * Test hasAccessToken.
     */
    public function testHasAccessToken(): void
    {
        // arrange
        $service = 'Facebook';
        $this->storage->clearToken($service);

        // act
        // assert
        self::assertFalse($this->storage->hasAccessToken($service));
    }

    /**
     * Check that the token gets properly deleted.
     */
    public function testStorageClears(): void
    {
        // arrange
        $service = 'Facebook';
        $token = new StdOAuth2Token('access', 'refresh', StdOAuth2Token::EOL_NEVER_EXPIRES, ['extra' => 'param']);

        // act
        $this->storage->storeAccessToken($service, $token);
        $this->storage->clearToken($service);

        // assert
        $this->expectException('OAuth\Common\Storage\Exception\TokenNotFoundException');
        $this->storage->retrieveAccessToken($service);
    }
}
