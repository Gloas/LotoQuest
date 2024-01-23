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
              $this->_tag('title', 'Loto Quest')];
        return $this->_tag('head' , implode('', $head));
    }


    protected function _body(): string {
        return $this->_tag('body',
                           implode([$this->_header(),
                                    $this->_main(),
                                    $this->_footer()]),
        ['class' => 'bg-info-subtle']);
    }


    protected function _header(): string {
        $links = [];

        $loto_ids = SortedDonations::getInstance()->getLotos();

        rsort($loto_ids);

        foreach ($loto_ids as $loto_id)
            $links [] = $this->_tag('li',
                                    $this->_anchor('#',
                                                   'Loto ' . $loto_id,
                                                   ['class' => 'nav-link btn btn-info py-1 dropdown-toggle'
                                                    . $this->_active(sprintf('/%s/', $loto_id)),
                                                    'data-bs-toggle' => 'dropdown',
                                                    'role' => 'button',
                                                    'data-toggle' => 'dropdown',
                                                    'aria-expanded' => 'false'])
                                    . $this->_dropdownMenu($loto_id),
                                    ['class' => 'nav-item dropdown']);

        $title = '';
        if ( $this->_partie_id)
            $title =
                $this->_tag('h1',
                            sprintf('Loto %s, partie %s %s',
                                    $this->_loto_id,
                                    str_replace('_', ' ', $this->_partie_id),
                                    str_replace('_', ' ', $this->_round_id)),
                            ['class' => 'col navbar-text py-1 my-1 h6 pe-3 text-end']);

        $brand = $this->_tag('div',
                             $this->_anchor('/', $this->_tag('img',
                                                             '',
                                                             ['src' => 'https://www.ape-valleiry.fr/wp-content/uploads/2017/09/logo_transparent-300x223.png',
                                                             'class' => 'mr-1'])
                                            . 'Accueil', ['class' => 'navbar-brand btn btn-info']),
                             ['class' => '']);

        $ul = $this->_tag('ul', implode($links), ['class' => 'col navbar-nav me-auto']);
        $nav = $this->_tag('nav',
                           $brand
                           . $ul
                           . ($this->_partie_id ? $this->_showPartieMenu($this->_partie_id) : '')
                           . $title,
                           ['class' => 'navbar navbar-expand bg-info p-0 mb-3']);
        return $this->_tag('header', $nav);
    }


    protected function _dropdownMenu(string $loto_id): string
    {
        $parties_links = SortedDonations::getInstance()->getNumberOfPartiesIn($loto_id);
        $links = [];
        for ($i = 1; $i <= $parties_links; $i++)
        {
            $url = '/' . $loto_id . '/' . (string) $i;
            $links [] = $this->_tag('li', $this->_anchor($this->_url($url, true),
                                                         'Partie n°' . $i,
                                                         ['class' => 'dropdown-item btn btn-info' . $this->_active($url)]));
        }

        $gros_lot_url = '/' . $loto_id . '/gros_lot';
        $links [] = $this->_tag('li', $this->_anchor($this->_url($gros_lot_url, true),
                                                     'Gros lot',
                                                     ['class' => 'dropdown-item btn btn-info' . $this->_active($gros_lot_url)]));

        $pas_de_bol_url = '/' . $loto_id . '/pas_de_bol';
        $links [] = $this->_tag('li', $this->_anchor($this->_url($pas_de_bol_url, true),
                                                     'Pas de bol',
                                                     ['class' => 'dropdown-item btn btn-info'. $this->_active($pas_de_bol_url)]));

        return $this->_tag('ul',
                           implode($links),
                           ['class' => 'dropdown-menu bg-info border-info-subtle']);
    }


    protected function _main(): string {
        $content = '';

        $donators = SortedDonations::getInstance()->donators($this->_loto_id,
                                                             $this->_partie_id,
                                                             $this->_round_id);

        if ($donators && $this->_loto_id)
            $content .= $this->_row($this->_wall($donators));

        if ($donators && ! $this->_loto_id)
            $content .= $this->_row($this->_wallImg($donators))
                . $this->_tag('script', 'setTimeout(() => {window.location.reload();}, 60000);');

        $html = [];
        if ( $this->_partie_id && $this->_round_id)
            $html = array_merge($html,
                                [$this->_tag('span', $this->_showNumber() , ['class' => 'mb-1 pb-1 text-bg-info current_number d-inline-block']),
                                 $this->_numberTable()]);

        $content .= $this->_row($html);
        if ( $this->_round_id)
            $content .= $this->_row($this->_anchor($this->_url('random'),
                                                   'Tirer un nombre',
                                                   ['class' => 'btn btn-lg btn-info play']));

        return $this->_tag('main',
                           $this->_tag('div',
                                       $content,
                                       ['class' => 'container-fluid text-center p-0 m-0']));
    }


    protected function _showNumber(): string
    {
        if ( $number = $this->_randomNumber())
            return $number;

        if ( $number = $this->_previousNumber())
            return $number;

        return '?';
    }


    protected function _previousNumber(): string
    {
        if ( ! $last = end($this->_number_table_in_memory))
            return '';

        if ( ! is_array($last))
            return '';

        return (string) reset($last);
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

        return $this->_tag('div', implode($columns), ['class' => 'row align-items-center mx-0']);
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
                                                             $this->_tag('h5', $this->_formatText($donation[0] ?? ''), ['class' => 'card-title'])
                                                             . $this->_tag('h6', $donation[2] ?? '', ['class' => 'card-subtitle mb-2']),

                                                             ['class' => 'card-body p-1 m-0'])
                                               . $this->_tag('div',
                                                             $this->_tag('p', $this->_formatText($donation[3] ?? ''), ['class' => 'card-text']),
                                                             ['class' => 'card-footer']),
                                               ['class' => 'card p-1 m-1 border-0 bg-dark bg-opacity-50 text-light h-100']),
                                   ['class' => 'col-1 px-0 pb-3']);

        return $this->_tag('div',
                           implode($html),
                           ['class' => 'row mx-0 justify-content-center']);
    }


    protected function _wallImg(array $donations): string
    {
        $html = [];
        shuffle($donations);
        foreach($donations as $donation)
            $html [] = $this->_tag('div',
                                   $this->_tag('div',
                                               $this->_tag('img', '', ['src' => $donation[6] ?? '' ,
                                                                       'class' => 'card-img']),
                                               ['class' => 'card']),
                                   ['class' => 'col-lg-2 mb-4']);

        return $this->_tag('div',
                           implode($html),
                           ['class' => 'row mx-0 masonry',
                            'data-masonry' => htmlspecialchars('{"percentPosition": true}')]);
    }


    protected function _formatText(string $text): string
    {
        if ( ! $text)
            return '';

        return ucwords(strtolower($text));
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
        {
            $url = sprintf('/%s/%s/%s',
                           $this->_loto_id,
                           $this->_partie_id,
                           $round_name);
            $links [] = $this->_anchor($this->_url($url, true), 
                                       ucfirst(str_replace('_', ' ', $round_name)),
                                       ['class' => 'btn btn-info' . $this->_active($url)]);
        }
        return $this->_tag('div',
                           implode($links),
                           ['class' => 'col btn-group justify-content-center']);
    }


    protected function _active(string $url): string
    {
        return 0 === strpos($this->_request->getUri()->getPath(), $url)
            ? ' active'
            : '';
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

        $this->_response = $this->_response
            ->withHeader('Location', sprintf('/%s/%s/%s',
                                             $this->_loto_id,
                                             $this->_partie_id,
                                             $this->_round_id))
            ->withStatus(302);

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
                           ['class' => 'row mx-0']);
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


    protected function _isNumberVisible(): bool
    {
        if ($this->_number_table_counter === $this->_random_number)
            return true;

        if ( $this->_isNumberInMemory($this->_number_table_counter))
            return true;

        return false;
    }


    protected function _footer(): string
    {
        $html = [];
        if (!$this->_loto_id)
            $html [] = $this->_anchor('/reset',
                                      'Réinitialiser les tirages',
                                      ['onclick' => 'return confirm(\'Êtes-vous sûr ?\');',
                                       'class' => 'btn btn-sm btn-danger']);

        $html [] = $this->_tag('script', '', ['src' => '/assets/bootstrap.bundle.min.js']);
        $html [] = $this->_tag('script', '', ['src' => '/assets/masonry.pkgd.min.js']);
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
