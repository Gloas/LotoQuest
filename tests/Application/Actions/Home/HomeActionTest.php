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


    public function tearDown(): void
    {
        parent::tearDown();
        $this->_dispatch('/reset');
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
        $this->_dispatch('/enfant/3/double_quine');
        $this->assertStringContainsString('Tirer un nombre', $this->_body);
    }


    public function testNavIsInBody()
    {
        $this->assertStringContainsString('navbar navbar-expand-lg bg-primary p-0', $this->_body);
    }


    public function testNumberTableShouldNotBePresent()
    {
        $this->assertStringNotContainsString('table table-primary text-center', $this->_body);
    }


    public function testCurrentNumberShouldNotBePresent()
    {
        $this->assertStringNotContainsString('current_number', $this->_body);
    }


    public function testWithRandomParamOneNumberTableShouldBePresent()
    {
        $this->_dispatch('/adulte/1/quine/random');
        $this->assertStringContainsString('table table-bordered table-primary text-center', $this->_body);
    }


    public function testWithRandomParamOneCurrentNumberShouldBePresent()
    {
        $this->_dispatch('/adulte/1/quine/random');
        $this->assertStringContainsString('mb-1 pb-1 text-bg-primary current_number d-inline-block', $this->_body);
    }


    public function testWithRandomParamCurrentNumberInTableShouldBePresent()
    {
        $this->_dispatch('/adulte/1/quine/random');
        $this->assertStringContainsString('p-0 current_number_in_table text-bg-primary fw-bold', $this->_body);
    }


    public function testWithResetParamShouldReset()
    {
        $this->_dispatch('/reset');
        $this->assertStringContainsString('Loto', $this->_body);
    }


    public function testWithEntracteShouldShowDonators()
    {
        $this->_dispatch('/adulte/entracte_10_minutes_prix_3');
        $this->assertStringContainsString('Loto', $this->_body);
    }


    public function testWithOutroShouldShowDonatorsPng()
    {
        $this->_dispatch('/outro');
        $this->assertStringContainsString('Loto', $this->_body);
    }
}
