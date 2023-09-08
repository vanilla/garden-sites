<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites\Tests;

use Garden\Http\Mocks\MockHttpHandler;
use Garden\Sites\Clients\OrchHttpClient;
use PHPUnit\Framework\TestCase;

class OrchClientTest extends TestCase
{
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
