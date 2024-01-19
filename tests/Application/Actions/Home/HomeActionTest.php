<?php

declare(strict_types=1);

namespace Tests\Application\Actions;

use DI\Container;
use Tests\TestCase;
use App\Application\Actions\Home;


abstract class LotoQuestTestCase extends TestCase {
    protected string $_body = '';


    protected function _dispatch(string $url, array $params = []): self
    {
        $app = $this->getAppInstance();
        $response = $app->handle($this->createRequest('GET', $url));
        $this->_body = (string) $response->getBody();
        return $this;
    }
}




class HomeActionTest extends LotoQuestTestCase
{

    public function setUp(): void {
        parent::setUp();
        $this->_dispatch('/');
    }


    public function testBootstrapMinCssIsLoaded()
    {
        $this->assertStringContainsString('bootstrap.min.css', $this->_body);
    }


    public function testBootstrapBundleMinJsIsLoaded()
    {
        $this->assertStringContainsString('bootstrap.bundle.min.js', $this->_body);
    }


    public function testAnchorTirerUnNombreIsInBody()
    {
        $this->_dispatch('/kids/3/double_quine');
        $this->assertStringContainsString('Tirer un nombre', $this->_body);
    }


    public function testNavIsInBody()
    {
        $this->assertStringContainsString('navbar navbar-nav navbar-expand bg-body-tertiary', $this->_body);
    }


    public function testNumberTableShouldNotBePresent()
    {
        $this->assertStringNotContainsString('table table-info text-center', $this->_body);
    }


    public function testCurrentNumberShouldNotBePresent()
    {
        $this->assertStringNotContainsString('current_number', $this->_body);
    }


    public function testWithRandomParamOneNumberTableShouldBePresent()
    {
        $this->_dispatch('/adults/1/quine/random');
        $this->assertStringContainsString('table table-info text-center', $this->_body);
    }


    public function testWithRandomParamOneCurrentNumberShouldBePresent()
    {
        $this->_dispatch('/adults/1/quine/random');
        $this->assertStringContainsString('badge rounded-pill text-bg-success current_number', $this->_body);
    }
}
