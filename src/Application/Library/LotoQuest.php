<?php

declare(strict_types=1);

namespace App\Application\Library;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


class LotoQuest
{
    protected Request $_request;
    protected Response $_response;
    protected array $_args;
    protected int $_number_table_counter = 1;
    protected int $_random_number = 0;
    protected array $_number_table_in_memory;
    protected string $_memory_file;

    protected string $_loto_id;
    protected string $_partie_id;
    protected string $_round_id;
    protected string $_pick_a_random_number;


    public function __construct(Request $request, Response $response, array $args)
    {
        $this->_request = $request;
        $this->_response = $response;
        $this->_args = $args;
        $this->_loto_id = $args['loto'] ?? '';
        $this->_partie_id = $args['partie_id'] ?? '';
        $this->_round_id = $args['round_name'] ?? '';
        $this->_pick_a_random_number = $args['random'] ?? '';
    }


    public function printInResponseBody(): self
    {
        $this->_initMemory();
        $this->_response->getBody()->write($this->_render());
        return $this;
    }


    public function response(): Response {
        return $this->_response;
    }


    protected function _initMemory(): static {
        if ( ! $this->_loto_id)
            return $this;

        if ( ! $this->_partie_id)
            return $this;

        $this->_memory_file = __DIR__ . '/../../../csv/'
            . $this->_loto_id
            . '/'
            . $this->_partie_id
            . '.csv';

        if ( !file_exists($this->_memory_file))
            touch($this->_memory_file);

        $this->_number_table_in_memory = array_map('str_getcsv', file($this->_memory_file));
        return $this;
    }


    protected function _render(): string {
        $html = [$this->_head(),
                 $this->_body()];
        return implode($html);
    }


    protected function _head(): string {
        $head = [$this->_tag('link', '', ['href' => '/assets/lotoquest.css',
                                          'rel' => 'stylesheet']),
                 $this->_tag('link', '', ['href' => '/assets/bootstrap.min.css',
                                          'rel' => 'stylesheet']),
                 $this->_tag('script', '', ['src' => '/assets/bootstrap.bundle.min.js'])];
        return $this->_tag('head' , implode('', $head));
    }


    protected function _body(): string {
        return implode([$this->_header(),
                        $this->_main(),
                        $this->_footer()]);
    }


    protected function _header(): string {
        $links = [$this->_tag('li', $this->_anchor('/', 'Accueil', ['class' => 'nav-link']), ['class' => 'nav-item'])];
        $nav = $this->_tag('nav', implode($links), ['class' => 'navbar navbar-nav navbar-expand bg-body-tertiary']);
        return $this->_tag('header', $nav);
    }


    protected function _main(): string {
        $content = '';
        if ( $this->_round_id)
            $content .= $this->_anchor($this->_url('random'), 'Tirer un nombre', ['class' => 'btn btn-lg btn-primary']);

        if ( $this->_pick_a_random_number)
            $content .= $this->_tag('span', $this->_randomNumber(), ['class' => 'badge rounded-pill text-bg-success current_number'])
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

        $fp = fopen(__DIR__ . '/../../../csv/adults/1.csv', 'a');
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

        return $this->_tag('table', implode($all_tr), ['class' => 'table table-info text-center']);
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


    protected function _url(string $url, bool $reset = false): string
    {
        if ( $reset)
            return $url;

        $new_url = [];
        foreach (array_filter([$this->_loto_id,
                                $this->_partie_id,
                               $this->_round_id,
                               $url])
            as $id)
            $new_url []= $id;

        return '/' . implode('/', $new_url);
    }
}
