<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Tests;

use Garden\Http\Mocks\MockHttpHandler;
use Garden\Sites\Clients\OrchHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the orch http client.
 */
class OrchClientTest extends TestCase
{
    /**
     * Test that our IP kludging (used sometimes in local dev) works.
     *
     * @return void
     */
    public function testForcedIpAddress()
    {
        $client = new OrchHttpClient("https://orch.vanilla.localhost", "secret");
        $client->setHandler(new MockHttpHandler());
        $client->setThrowExceptions(false);
        $client->forceIpAddress("12.34.56.78");

        $request = $client->get("/hello")->getRequest();
        $this->assertEquals("https://12.34.56.78/hello", $request->getUrl());
        $this->assertEquals("orch.vanilla.localhost", $request->getHeader("Host"));
    }
}
