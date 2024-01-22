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
    protected bool $_pick_a_random_number;



    public static function resetParties(): void
    {
        $adults_files = glob(__DIR__ . '/../../../csv/adulte/*');
        $kids_files = glob(__DIR__ . '/../../../csv/enfant/*');
        foreach(array_merge($adults_files, $kids_files) as $file)
            if (is_file($file))
                unlink($file);
    }


    public function __construct(Request $request, Response $response, array $args)
    {
        $this->_request = $request;
        $this->_response = $response;
        $this->_args = $args;
        $this->_loto_id = $args['loto'] ?? '';
        $this->_partie_id = $args['partie_id'] ?? '';
        $this->_round_id = $args['round_name'] ?? '';
        $this->_pick_a_random_number = ($args['random'] ?? '') === 'random';
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
        $head =
            [ $this->_tag('meta', '', ['charset' => 'utf-8']),
              $this->_tag('meta', '',
                          ['nawe' => 'viewport',
                           'content' => 'width=device-width, initial-scale=1']),
              $this->_tag('link', '', ['href' => '/assets/bootstrap.min.css',
                                       'rel' => 'stylesheet']),
              $this->_tag('link', '', ['href' => '/assets/lotoquest.css',
                                       'rel' => 'stylesheet']),
              $this->_tag('title', 'Loto Quest'),
              $this->_tag('script', '', ['src' => '/assets/bootstrap.bundle.min.js'])
            ];
        return $this->_tag('head' , implode('', $head));
    }


    protected function _body(): string {
        return implode([$this->_header(),
                        $this->_main(),
                        $this->_footer()]);
    }


    protected function _header(): string {
        $links = [$this->_tag('li',
                              $this->_anchor('/', 'Accueil', ['class' => 'nav-link py-1']),
                              ['class' => 'nav-item'])];

        $loto_ids = SortedDonations::getInstance()->getLotos();

        foreach ($loto_ids as $loto_id)
            $links [] = $this->_tag('li',
                                    $this->_anchor('#',
                                                   'Loto ' . $loto_id,
                                                   ['class' => 'nav-link py-1 dropdown-toggle',
                                                    'data-bs-toggle' => 'dropdown',
                                                    'role' => 'button',
                                                    'data-toggle' => 'dropdown',
                                                    'aria-expanded' => 'false'])
                                    . $this->_dropdownMenu($loto_id),
                                    ['class' => 'nav-item dropdown']);

        $title =
            $this->_tag('h1',
                        sprintf('Loto %s, partie %s, %s',
                                $this->_loto_id,
                                str_replace('_', ' ', $this->_partie_id),
                                str_replace('_', ' ', $this->_round_id)),
                        ['class' => 'navbar-text py-0 my-0 h3 pe-3']);

        $ul = $this->_tag('ul', implode($links), ['class' => 'navbar-nav mr-auto me-auto']);
        $nav = $this->_tag('nav', $ul . $title, ['class' => 'navbar navbar-expand bg-body-tertiary p-0']);
        return $this->_tag('header', $nav);
    }


    protected function _dropdownMenu(string $loto_id): string
    {
        $parties_links = SortedDonations::getInstance()->getNumberOfPartiesIn($loto_id);
        $links = [];
        for ($i = 1; $i <= $parties_links; $i++)
            $links [] = $this->_tag('li', $this->_anchor($this->_url('/' . $loto_id . '/' . (string) $i, true),
                                                         'Partie n°' . $i,
                                                         ['class' => 'dropdown-item']));

        $links [] = $this->_tag('li', $this->_anchor($this->_url('/' . $loto_id . '/gros_lot', true),
                                                     'Gros lot',
                                                     ['class' => 'dropdown-item']));
        $links [] = $this->_tag('li', $this->_anchor($this->_url('/' . $loto_id . '/pas_de_bol', true),
                                                     'Pas de bol',
                                                     ['class' => 'dropdown-item']));

        return $this->_tag('ul',
                           implode($links),
                           ['class' => 'dropdown-menu']);
    }


    protected function _main(): string {
        $content = '';

        if ( $this->_partie_id)
            $content .= $this->_row($this->_showPartieMenu($this->_partie_id));

        $donators = SortedDonations::getInstance()->donators($this->_loto_id,
                                                             $this->_partie_id,
                                                             $this->_round_id);

        if ($donators)
            $content .= $this->_row($this->_wall($donators));

        $html = [];
        if ( $this->_partie_id && $this->_round_id)
            $html = array_merge($html,
                                [$this->_tag('span', $this->_randomNumber(), ['class' => 'badge rounded-pill text-bg-info current_number']),
                                 $this->_numberTable()]);

        $content .= $this->_row($html);
        if ( $this->_round_id)
            $content .= $this->_row($this->_anchor($this->_url('random'), 'Tirer un nombre', ['class' => 'btn btn-lg btn-primary']));

        return $this->_tag('main',
                           $this->_tag('div',
                                       $content,
                                       ['class' => 'container-fluid text-center p-0 m-0']));
    }


    protected function _row(array|string $html, int $cols = 12): string
    {
        if ( ! is_array($html))
            return
                $this->_tag('div',
                            $this->_tag('div',
                                        $html,
                                        ['class' => 'p-0 col-' . $cols]),
                            ['class' => 'row m-0 pb-3']);

        $columns = [];
        foreach($html as $element)
            $columns [] = $this->_tag('div', $element, ['class' => 'col-6 overflow-hidden']);

        return $this->_tag('div', implode($columns), ['class' => 'row']);
    }


    protected function _wall(array $donations): string
    {
        $html = [];
        foreach($donations as $donation)
            $html [] = $this->_tag('div',
                                   $this->_tag('div',
                                               (($donation[6] ?? '')
                                                ? $this->_tag('img', '', ['src' => $donation[6] ?? '' ,
                                                                          'class' => 'card-img-top img-thumbnail img-fluid'])
                                                : '')
                                               . $this->_tag('div',
                                                             $this->_tag('h5', $donation[3] ?? '', ['class' => 'card-title'])
                                                             . $this->_tag('p', $donation[0] ?? '', ['class' => 'card-text']),
                                                             ['class' => 'card-body p-1 m-0']),
                                               ['class' => 'card p-1 m-1 text-bg-info h-100']),
                                   ['class' => 'col-1 px-0 pb-3']);

        return $this->_tag('div',
                           implode($html),
                           ['class' => 'row justify-content-center']);
    }


    protected function _carousel(array $donations): string {
        $html = [];
        foreach($donations as $donation)
            $html [] = $this->_tag('div',
                                   $this->_tag('span', $donation[0] ?? '', ['class' => 'd-block w-100'])
                                   . (($donation[6] ?? '')
                                      ? $this->_tag('img', '', ['src' => $donation[6] ?? '' ,
                                                                'class' => 'd-block w-100'])
                                      : ''),
                                   ['class' => 'carousel-item' . (0 == count($html) ? ' active' : '')]);

        return $this->_tag('div',
                           $this->_tag('div',
                                       implode($html),
                                       ['class' => 'carousel-inner']),
                           ['id' => 'carouselUNIQUE', 
                            'class' => 'carousel slide carousel-dark',
                            'data-bs-ride' => 'carousel']);
    }


    protected function _showPartieMenu(string $partie_id): string
    {
        $links = [];
        $rounds = ['quine', 'double_quine', 'carton'];

        if ( $partie_id == 'gros_lot' || $partie_id == 'pas_de_bol')
            $rounds = ['carton'];

        foreach($rounds as $round_name)
            $links [] = $this->_tag('li', $this->_anchor($this->_url(sprintf('/%s/%s/%s',
                                                                             $this->_loto_id,
                                                                             $this->_partie_id,
                                                                             $round_name), true),
                                                         ucfirst(str_replace('_', ' ', $round_name)),
                                                         ['class' => 'nav-link py-1 px-2']),
                                    ['class' => 'nav-item']);
        return $this->_tag('ul',
                           implode($links),
                           ['class' => 'nav justify-content-center']);
    }


    protected function _randomNumber(): string {
        if ( $this->_isMemoryFull())
            return '';

        if ( ! $this->_pick_a_random_number)
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

        $fp = fopen(sprintf('%s/../../../csv/%s/%s.csv',
                            __DIR__,
                            $this->_loto_id,
                            $this->_partie_id), 'a');
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

        return
            $this->_tag('div',
                        $this->_tag('div',
                                    $this->_tag('table', implode($all_tr), ['class' => 'table table-bordered table-info text-center']),
                                    ['class' => 'col-10']),
                           ['class' => 'row']);
    }


    protected function _numberTableTd(): string {
        $all_td = [];
        for ($col = 1; $col <= 10; $col ++) {
            $all_td [] = $this->_tag('td',
                                     (string) $this->_number_table_counter,
                                     ['class' => 'fs-2' . ($this->_isNumberVisible()
                                                       ? ''
                                                       : ' invisible')]);
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
        $html = [];
        if (!$this->_loto_id)
            $html [] = $this->_anchor('/reset',
                                      'Réinitialiser les tirages',
                                      ['onclick' => 'return confirm(\'Êtes-vous sûr ?\');',
                                       'class' => 'btn btn-sm btn-danger']);

        return $this->_tag('footer', $this->_row(implode($html)), ['class' => 'text-center']);
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

        return '/' . implode('/', array_unique($new_url));
    }
}
