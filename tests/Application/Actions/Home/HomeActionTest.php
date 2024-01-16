<?php

declare(strict_types=1);

namespace Tests\Application\Actions;

use DI\Container;
use Tests\TestCase;
use App\Application\Actions\Home;

class HomeActionTest extends TestCase
{
    public function testAction()
    {
        $app = $this->getAppInstance();

        $request = $this->createRequest('GET', '/');
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $this->assertEquals('ueuie', $payload);
    }
}
