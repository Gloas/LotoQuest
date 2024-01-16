<?php

declare(strict_types=1);

namespace App\Application\Actions\Home;

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


    public function __invoke(Request $request, Response $response, array $args = []): Response
    {
        $this->_request = $request;
        $this->_response = $response;
        $this->_args = $args;

        return $this->_action();
    }


    protected function _action(): Response {
        $this->_initMemory();
        $this->_response->getBody()->write($this->_render());
        return $this->_response;
    }


    protected function _initMemory(): static {
        $this->_number_table_in_memory = array_map('str_getcsv', file(__DIR__ . '/../../../../csv/adults/1.csv'));
        return $this;
    }


    protected function _render(): string {
        $html = [$this->_head(),
                 $this->_body()];
        return implode($html);
    }


    protected function _head(): string {
        return '';
    }


    protected function _body(): string {
        return implode([$this->_header(),
                        $this->_main(),
                        $this->_footer()]);
    }


    protected function _header(): string {
        $links = [$this->_tag('li', $this->_anchor('/', 'Accueil'))];
        $nav = $this->_tag('nav', implode($links));
        return $this->_tag('header', $nav);
    }


    protected function _main(): string {
        $content = $this->_anchor('/?random=1', $this->_tag('button', 'Tirer un nombre'))
            . $this->_randomNumber()
            . $this->_numberTable();

        return $this->_tag('main', $content);
    }

    protected function _randomNumber(): string {
        if ( !$random = $this->_request->getQueryParams()['random'] ?? null)
            return '';

        if ( $this->_isMemoryFull())
            return '';

        $this->_random_number = rand(1, 90);
        return $this->_saveRandomNumber()
            ? (string) $this->_random_number
            : $this->_randomNumber();
    }


    protected function _isMemoryFull(): bool {
        return 90 <= count($this->_number_table_in_memory);
    }


    protected function _saveRandomNumber(): bool {
        if ($this->_isNumberInMemory($this->_random_number))
            return false;

        $fp = fopen(__DIR__ . '/../../../../csv/adults/1.csv', 'a');
        fputcsv($fp, [$this->_random_number]);
        fclose($fp);
        return true;
    }


    protected function _isNumberInMemory(int $number): bool {
        foreach($this->_number_table_in_memory as $array)
            if ( $number == reset($array))
                return true;

        return false;
    }


    protected function _numberTable(): string {
        $all_tr = [];
        $this->_number_table_counter = 1;

        for ($row = 1 ; $row <= 9 ; $row ++)
            $all_tr [] = $this->_tag('tr', $this->_numberTableTd());

        return $this->_tag('table', implode($all_tr));
    }


    protected function _numberTableTd(): string {
        $all_td = [];
        for ($col = 1; $col <= 10; $col ++) {
            $all_td [] = $this->_tag('td',
                                     (string) $this->_number_table_counter,
                                     $this->_isNumberVisible()
                                     ? []
                                     : ['style' => 'visibility:hidden']);
            $this->_number_table_counter++;
        }

        return implode($all_td);
    }


    protected function _isNumberVisible(): bool {
        if ($this->_number_table_counter === $this->_random_number)
                return true;

        if ( $this->_isNumberInMemory($this->_number_table_counter))
            return true;

        return false;
    }


    protected function _footer(): string {
        return $this->_tag('footer', '');
    }


    protected function _tag(string $tag, string $content, array $attribs = []): string {
        $html_attribs = '';
        foreach($attribs as $name => $value)
            $html_attribs .= sprintf('%s="%s"',
                                     $name,
                                     $value);

        return sprintf('<%s %s>%s</%s>',
                       $tag,
                       $html_attribs,
                       $content,
                       $tag);
    }


    protected function _anchor(string $url, string $content, array $attribs = []): string {
        return $this->_tag('a', $content, array_merge($attribs, ['href' => $url]));
    }


    protected function _img(string $url, array $attribs = []): string {
        return $this->_tag('img', '', array_merge($attribs, ['src' => $url]));
    }
}
