<?php

declare(strict_types=1);

namespace Tests\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Tests\TestCase;

abstract class LotoQuestTestCase extends TestCase
{
    protected const SESSION = 'testsession42';

    protected string $_body = '';
    protected ?Response $_response = null;


    protected function _dispatch(string $url, string $session = self::SESSION): self
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', $url, [], ['lotoquest_session' => $session]);
        $this->_response = $app->handle($request);
        $this->_body = (string) $this->_response->getBody();
        return $this;
    }


    protected function _drawsFile(string $loto, string $partie, string $session = self::SESSION): string
    {
        return sprintf('%s/%s/%s_%s.csv', $this->drawsDir, $loto, $session, $partie);
    }


    /** @return int[] */
    protected function _drawnNumbers(string $loto, string $partie, string $session = self::SESSION): array
    {
        $file = $this->_drawsFile($loto, $partie, $session);
        return file_exists($file)
            ? array_map('intval', file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            : [];
    }
}
