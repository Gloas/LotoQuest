<?php

declare(strict_types=1);

namespace App\Application\Actions\Home;

use App\Application\Library\LotoQuest as LotoQuest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


class ViewHomeAction
{
    protected Request $_request;
    protected Response $_response;
    protected array $_args;
    protected int $_number_table_counter = 1;
    protected int $_random_number = 0;
    protected array $_number_table_in_memory;
    protected bool $_outro = false;


    public function __invoke(Request $request, Response $response, array $args = []): Response
    {
        $this->_request = $request;
        $this->_response = $response;
        $this->_args = $args;

        if ( $this->_outro)
            $this->_args['outro'] = true;

        return $this->_action();
    }


    protected function _action(): Response {
        return (new LotoQuest($this->_request, $this->_response, $this->_args))
            ->printInResponseBody()
            ->response();
    }


    public function reset(Request $request): static
    {
        LotoQuest::resetParties($request);
        return $this;
    }


    public function outro(): static
    {
        $this->_outro = true;
        return $this;
    }
}
