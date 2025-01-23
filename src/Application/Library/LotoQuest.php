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
    protected int $_last_number = 0;
    protected int $_previous_number = 0;
    protected array $_number_table_in_memory;
    protected string $_memory_file;

    protected string $_loto_id;
    protected string $_partie_id;
    protected string $_entracte;
    protected string $_intro;
    protected string $_spectacle;
    protected string $_round_id;
    protected bool $_pick_a_random_number;
    protected string $_outro;
    protected Partie $_partie_spectacle;
    protected Partie $_partie_outro;
    protected Partie $_partie_intro;


    public static function resetParties(Request $request): void
    {
        $cookies = $request->getCookieParams();
        $cookie_id = (string) reset($cookies);

        $adults_files = glob(__DIR__ . '/../../../csv/adulte/*');
        $kids_files = glob(__DIR__ . '/../../../csv/enfant/*');
        foreach(array_merge($adults_files, $kids_files) as $file)
            if (is_file($file) && (false !== strpos($file, $cookie_id)))
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
        $this->_entracte = (0 === strpos($this->_partie_id, 'entracte')) ? $this->_partie_id : '';
        $this->_outro = (0 === strpos($this->_partie_id, 'outro')) ? $this->_partie_id : '';
        $this->_spectacle = (0 === strpos($this->_partie_id, 'spectacle')) ? $this->_partie_id : '';
        $this->_intro = (0 === strpos($this->_partie_id, 'intro')) ? $this->_partie_id : '';
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
            . $this->_cookieId() . '_' . $this->_partie_id
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
              $this->_tag('link', '', ['href' => BASE_PATH . '/assets/bootstrap.min.css',
                                       'rel' => 'stylesheet']),
              $this->_tag('link', '', ['href' => BASE_PATH . '/assets/fontawesome/css/all.min.css',
                                       'rel' => 'stylesheet']),
              $this->_tag('link', '', ['href' => BASE_PATH . '/assets/lotoquest.css',
                                       'rel' => 'stylesheet']),
              $this->_tag('title', 'Loto Quest ' . implode(' ',
                                                           array_filter([$this->_loto_id,
                                                                         $this->_partie_id,
                                                                         $this->_round_id])))];
        return $this->_tag('head' , implode('', $head));
    }


    protected function _body(): string {
        return $this->_tag('body',
                           implode([$this->_header(),
                                    $this->_main(),
                                    $this->_footer()]),
                           ['data-session' => $this->_cookieId(),
                            'class' => 'm-0 p-0 ' . implode(' ', array_filter([$this->_loto_id,
                                                                                                             $this->_partie_id,
                                                                                                             $this->_round_id]))]);
    }


    protected function _cookieId(): string
    {
        $cookies = $this->_request->getCookieParams();
        return (string) reset($cookies);
    }


    protected function _header(): string {
        $links = [];

        $loto_ids = SortedDonations::getInstance()->getLotos();

        rsort($loto_ids);

        foreach ($loto_ids as $loto_id)
            $links [] = $this->_tag('li',
                                    $this->_anchor('#',
                                                   $this->_lotoIco($loto_id) . 'Loto ' . $loto_id,
                                                   ['class' => 'btn py-1 dropdown-toggle'
                                                    . $this->_active(sprintf('/%s/', $loto_id)),
                                                    'data-bs-toggle' => 'dropdown',
                                                    'role' => 'button',
                                                    'data-toggle' => 'dropdown',
                                                    'aria-expanded' => 'false'])
                                    . $this->_dropdownMenu($loto_id),
                                    ['class' => 'nav-item dropdown']);

        $title = '';
        if ( $this->_partie_id && ! $this->_outro && ! $this->_intro && ! $this->_spectacle)
            $title = $this->_tag('h1',
                                 $this->_ico('fa-solid fa-play') . sprintf('Loto %s, partie %s %s',
                                         $this->_loto_id,
                                         str_replace('_', ' ', $this->_partie_id),
                                         str_replace('_', ' ', $this->_round_id)),
                                 ['class' => 'navbar-text fs-5 py-1 my-1  pe-3 text-center text-lg-end']);

        $brand = $this->_anchor( BASE_PATH . '/', $this->_tag('img',
                                                 '',
                                                 ['src' => BASE_PATH . '/assets/logo/ape.png',
                                                  'class' => 'me-1'])
                                . 'Accueil', ['class' => 'navbar-brand btn']);

        $ul = $this->_tag('ul', implode($links), ['class' => 'nav container-fluid justify-content-center']);

        $rounds_menu = '';
        if ( $this->_partie_id)
            $rounds_menu = implode($this->_showRoundsMenu($this->_partie_id));

        $nav_content = $this->_tag('div',
                                   $this->_tag('div',
                                               $brand,
                                               ['class' => 'col-6 col-lg-2 order-1 text-center p-1'])
                                   . $this->_tag('div',
                                                 $ul,
                                                 ['class' => 'col-12 col-lg-3 order-3 order-lg-2 text-center p-1'])
                                   . $this->_tag('div',
                                                 $rounds_menu,
                                                 ['class' => 'col-12 col-lg-4 order-4 p-1 mt-3 mt-lg-0 btn-group justify-content-center' . ($rounds_menu ? ' ' : '')])
                                   . $this->_tag('div' ,
                                                 $title,
                                                 ['class' => 'col-6 col-lg-3 navbar-text p-1 h6 pe-lg-3 text-center text-lg-end order-2 order-lg-4']),
                                   ['class' => 'container-fluid p-0']);

        $nav = $this->_tag('nav',
                           $nav_content,
                           ['class' => 'navbar navbar-expand-lg p-1 p-lg-3']);

        return $this->_tag('header', $nav, ['class' => 'mb-1']);
    }


    protected function _dropdownMenu(string $loto_id): string
    {
        $parties_row = SortedDonations::getInstance()->getPartiesIn($loto_id);
        $links = [];
        foreach ( $parties_row as $partie_array)
        {
            $partie = new Partie($partie_array);

            if ( $partie->isSpectacle())
                $this->_partie_spectacle = $partie;

            if ( $partie->isIntro())
                $this->_partie_intro = $partie;

            if ( $partie->isOutro())
                $this->_partie_outro = $partie;

            $url = $loto_id . '/' . $partie->getId();
            $links [] = $this->_tag('li', $this->_anchor($this->_url($url . $partie->getFirstRound(), true),
                                                         ucfirst($partie->getAnchorLabel()),
                                                         ['class' => 'dropdown-item' . $this->_active($url)]));
        }

        // $links [] = $this->_tag('li', $this->_anchor($this->_url('outro', true),
        //                                              'Outro',
        //                                              ['class' => 'dropdown-item' . $this->_active('/outro')]));

        return $this->_tag('ul',
                           implode($links),
                           ['class' => 'dropdown-menu']);
    }


    protected function _main(): string {
        $content = '';

        if ( $this->_outro)
            return $this->_outro();

        if ( $this->_spectacle)
            return $this->_spectacle();

        $donators = SortedDonations::getInstance()->donators($this->_loto_id,
                                                             $this->_partie_id,
                                                             $this->_round_id);

        if ($donators && $this->_loto_id && ! $this->_entracte)
            $content .= $this->_col($this->_wall($donators), 'col-12 mb-1');

        if ( (0 === strpos($this->_partie_id, 'surprise')) && $this->_round_id)
            $content .=
                $this->_col($this->_anchor('#',
                                           $this->_ico('fa-solid fa-eye') . 'Voir les lots ?',
                                           ['onclick' => htmlspecialchars('document.querySelector(\'.donators_row\').style.visibility = "visible"; document.querySelector(\'.reveal\').style.visibility = "hidden"'),
                                            'class' => 'btn btn-lg btn-dark reveal fs-1 mb-3']),
                            'col-12');


        if ($donators && (! $this->_loto_id || $this->_entracte))
            $content .= $this->_col($this->_wallImg($donators), 'col-12')
                . $this->_tag('script', 'setInterval(() =>
{
var ul = document.querySelector(\'.masonry\');
for (var i = ul.children.length; i >= 0; i--) {
    ul.appendChild(ul.children[Math.random() * i | 0]);
}
new Masonry(ul);
}, 30000);setTimeout(() => {
var ul = document.querySelector(\'.masonry\');
new Masonry(ul);
}, 400);');

        if ( $this->_partie_id && $this->_round_id)
            $content .=
                $this->_col($this->_tag('span',
                                        $this->_showNumber() ,
                                        ['class' => 'mb-1 pb-1 rounded current_number d-inline-block']),
                            'col col-12 col-lg-6 mb-3 mb-lg-0 p-1 p-lg-5')
                . $this->_col($this->_numberTable(),
                              'col col-12 col-lg-6 mb-3 mb-lg-0 p-1 pb-5 p-lg-5');

        if ( $this->_round_id)
            $content .= $this->_col($this->_anchor($this->_url('random'),
                                                   $this->_ico('fa-solid fa-dice') . 'Tirer un nombre',
                                                   ['class' => 'btn btn-lg play disabled w-100']),
                                    'col-12 fixed-bottom mb-1')
                . $this->_tag('script',
                              sprintf('setTimeout(function() {document.querySelector(".play.disabled").classList.remove("disabled");}, %d);',
                                      ('enfant' == $this->_loto_id) ? 6500 : 4000));

        return $this->_tag('main', $this->_container($this->_row($content)));
    }


    protected function _container(string $html): string
    {
        return $this->_tag('div', $html, ['class' => 'container-fluid text-center p-0 m-0']);
    }


    protected function _col(string $html, string $class = 'col col-12 col-lg-6'): string
    {
        return $this->_tag('div', $html, ['class' => $class]);
    }


    protected function _outro(): string
    {
        if ( ! $this->_partie_outro)
            return '';

        $outro = $this->_partie_outro;
        $content = $this->_tag('row', $this->_tag('h1', $this->_ico('fa-regular fa-hand-peace') . $outro->getThanksMessage(), ['class' => 'thanks_title pt-3']));

        $imgs = $this->_tag('div', $this->_img('assets/' . $outro->getThanksDonatorsImg(), ['class' => 'img-fluid px-3 pb-0 ']), ['class' => 'col-12 col-lg-5 d-inline-block'])
            . $this->_tag('div', $this->_img('assets/' . $outro->getThanksVolunteersImg(), ['class' => 'img-fluid px-5 pb-0 pt-5']), ['class' => 'col-12 col-lg-5 d-inline-block']);

        $content .= $this->_tag('div', $imgs, ['class' => 'row m-0 justify-content-center']);
        return $this->_tag('main',
                           $this->_tag('div',
                                       $content,
                                       ['class' => 'container-fluid text-center p-0 m-0']));
    }


    protected function _spectacle(): string
    {
        if ( ! $this->_partie_spectacle)
            return '';

        $spectacle = $this->_partie_spectacle;
        $content = $this->_tag('row', $this->_tag('h1', $this->_ico('fa-solid fa-star') . $spectacle->getShowTitle(), ['class' => 'show_title pt-3']))
           . $this->_tag('row', $this->_tag('p', $spectacle->getShowDesc(), ['class' => 'show_desc pt-3 col-8 d-inline-block']));

        return $this->_tag('main',
                           $this->_tag('div',
                                       $content,
                                       ['class' => 'container-fluid text-center p-0 m-0']));
    }


    protected function _showNumber(): string
    {
        if ( $number = $this->_randomNumber())
            return $number;

        if ( $number = $this->_lastNumber())
            return (string) $number;

        return '?';
    }


    protected function _lastNumber(): int
    {
        if ( ! $last = end($this->_number_table_in_memory))
            return 0;

        if ( ! is_array($last))
            return 0;

        return $this->_last_number = (int) reset($last);
    }


    protected function _previousNumber(): int
    {
        $copy_array = $this->_number_table_in_memory;

        array_pop($copy_array);

        if ( ! $last = end($copy_array))
            return 0;

        if ( ! is_array($last))
            return 0;

        return $this->_previous_number = (int) reset($last);
    }


    protected function _row(string $html, string $class = 'row g-0'): string
    {
        return $this->_tag('div', $html, ['class' => $class]);
    }


    protected function _wall(array $donations): string
    {
        $html = [];
        foreach($donations as $donation)
            $html [] = $this->_tag('div',
                                   (($donation[6] ?? '')
                                    ? $this->_tag('img', '',
                                                  ['src' => BASE_PATH . '/assets/logo/' . $donation[6] ?? '' ,
                                                   'class' => 'card-img-top'])
                                    : '')
                                   . $this->_tag('div',
                                                 $this->_tag('h5',
                                                             $this->_formatText($donation[0] ?? ''),
                                                             ['class' => 'card-title'])
                                                 . $this->_tag('p', $this->_formatText($donation[3] ?? ''),
                                                               ['class' => 'card-text']),
                                                 ['class' => 'card-body'])
                                   . $this->_tag('div',
                                                 $this->_tag('small', $donation[2] ?? ''),
                                                 ['class' => 'card-footer price']),
                                   ['class' => 'card border-dark m-1']);

        return $this->_tag('div',
                           $this->_tag('div', implode($html), ['class' => 'card-group']),
                           ['class' => 'd-inline-block align-self-center donators_row']);
    }



    protected function _wallImg(array $donations): string
    {
        $html = [];
        shuffle($donations);
        foreach($donations as $donation)
            $html [] = $this->_tag('div',
                                   $this->_tag('div',
                                               $this->_tag('img', '', ['src' => BASE_PATH . '/assets/logo/' . $donation[6] ?? '',
                                                                       'alt' => $donation[0] ?? '',
                                                                       'class' => 'card-img masonry_img']),
                                               ['class' => 'card p-1 bg-transparent border-0']),
                                   ['class' => 'col-4 col-sm-3 col-lg-2 m-0 p-0']);

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
                                      ? $this->_tag('img', '', ['src' => BASE_PATH . '/assets/logo/' . $donation[6] ?? '' ,
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


    protected function _showRoundsMenu(string $partie_id): array
    {
        if ( $this->_outro || $this->_intro || $this->_spectacle)
            return [];

        if ( $this->_entracte)
        {
            xdebug_break();
            $entracte = new Partie(explode('_', $this->_entracte));
            return [
                $this->_tag('span', $this->_ico('fa-solid fa-fire-flame-curved text-danger') . sprintf('Carton à %d€', $entracte->getPrix()), ['class' => 'prix fs-1  me-5']),
                $this->_tag('span', $this->_ico('fa-solid fa-stopwatch'), ['class' => 'fs-1']) . $this->_tag('span', (string) $entracte->getMinutes(), ['class' => 'timer fs-1 fw-bold ']),
                $this->_tag('script', '
var timeLimitInMinutes = ' . $entracte->getMinutes() . ';'
                            . 'var timeLimitInSeconds = timeLimitInMinutes * 60;
var timerElement = document.querySelector(\'.timer\');

function startTimer() {
  timeLimitInSeconds--;
  var minutes = Math.floor(timeLimitInSeconds / 60);
  var seconds = timeLimitInSeconds % 60;

  if (timeLimitInSeconds < 0) {
    timerElement.textContent = \'00:00\';
    clearInterval(timerInterval);
    return;
  }

  if (minutes < 10)
    minutes = \'0\' + minutes;

  if (seconds < 10)
    seconds = \'0\' + seconds;

  timerElement.textContent = minutes + \':\' + seconds;
}

var timerInterval = setInterval(startTimer, 1000);
')];
        }

        $links = [];
        $rounds = ['quine', 'double_quine', 'carton'];

        if ( in_array($partie_id, ['gros_lot',
                                   'pas_de_bol',
                                   'surprise_bleu',
                                   'surprise_verte',
                                   'surprise_rouge']))
            $rounds = ['carton'];



        foreach($rounds as $round_name)
        {
            $url = sprintf('%s/%s/%s',
                           $this->_loto_id,
                           $this->_partie_id,
                           $round_name);
            $links [] = $this->_anchor($this->_url($url, true), 
                                       $this->_roundIco($round_name) . ucwords(str_replace('_', ' ', $round_name)),
                                       ['class' => 'round fs-4 btn-lg btn' . $this->_active($url)]);
        }

        return $links;
    }


    protected function _active(string $url): string
    {
        return false !== strpos($this->_request->getUri()->getPath(), $url)
            ? ' active current_round'
            : '';
    }


    protected function _randomNumber(): string {
        if ( $this->_isMemoryFull())
            return '';

        if ( ! $this->_pick_a_random_number)
            return '';

        $this->_random_number = random_int(1, 90);
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
                            $this->_cookieId() . '_' . $this->_partie_id), 'a');
        fputcsv($fp, [$this->_random_number]);
        fclose($fp);

        $this->_response = $this->_response
            ->withHeader('Location', sprintf('%s/%s/%s/%s',
                                             BASE_PATH,
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
        $this->_number_table_counter = 0;

        for ($row = 0 ; $row <= 9 ; $row ++)
            $all_tr [] = $this->_tag('tr', $this->_numberTableTd($row));

        return
            $this->_row(
                $this->_col($this->_tag('table', implode($all_tr),
                                        ['class' => 'table table-responsive table-striped table-bordered table-primary text-center']),
                                    'col-10')
                        . $this->_tag('button',
                                    $this->_tag('i', '', ['class' => 'fa-solid fa-table-cells']),
                                    ['class' => 'position-absolute top-0 end-0 col-1 btn btn-sm show_table',
                                     'onclick' => 'document.querySelector(\'.table\').classList.toggle(\'show\');'])
                        . $this->_tag('button',
                                    $this->_tag('i', '', ['class' => 'fa-solid fa-timeline']),
                                    ['class' => 'position-absolute top-50 end-0 col-1 btn btn-sm show_table_picked',
                                     'onclick' => 'document.querySelector(\'.table\').classList.toggle(\'show_picked\');'])
                        . $this->_tag('span',
                                      floor(count($this->_number_table_in_memory) / 90 * 100) . '%',
                                      ['class' => 'position-absolute bottom-0 end-0 col-1 btn btn-sm fw-lighter percent']),
                        'row mx-0 position-relative justify-content-center');
    }


    protected function _numberTableTd(int $row): string {
        $all_td = [];
        $this->_number_table_counter = $row;
        for ($col = 1; $col <= 10; $col ++) {
            $all_td [] = $this->_tag('td',
                                     (string) $this->_number_table_counter,
                                     ['class' => 'p-0'
                                      . ($this->_isForbiden() ? ' not_in_loto' : '')
                                      . ($this->_isNumberVisible() ? '' : ' invisible')
                                      . ($this->_isNumberPicked() ? ' picked' : '')
                                      . ($this->_isPreviousNumber() ? ' previous_number_in_table' : '')
                                      . ($this->_isCurrentNumber() ? ' current_number_in_table fw-bold' : '')
                                      ]);
            $this->_number_table_counter+=10;
        }

        return implode($all_td);
    }


    protected function _isForbiden(): bool
    {
        return in_array($this->_number_table_counter, [0,91, 92, 93, 94, 95, 96, 97, 98 ,99]);
    }

    protected function _isNumberPicked(): bool
    {
        return in_array($this->_number_table_counter, $this->_pickedNumbers());
    }


    protected function _pickedNumbers(): array
    {
        return array_map(fn($e) => reset($e), $this->_number_table_in_memory);
    }


    protected function _isPreviousNumber(): bool
    {
        return $this->_number_table_counter
            && $this->_number_table_counter == $this->_previousNumber()
            && ! $this->_isCurrentNumber();
    }


    protected function _isCurrentNumber(): bool
    {
        return $this->_number_table_counter == ($this->_random_number ? $this->_random_number : $this->_last_number);
    }


    protected function _isNumberVisible(): bool
    {
        if ( ! $this->_number_table_counter)
            return false;

        if ($this->_number_table_counter === $this->_random_number)
            return true;

        if ( $this->_isNumberInMemory($this->_number_table_counter))
            return true;

        return false;
    }


    protected function _footer(): string
    {
        $html = [];
        if (!$this->_loto_id && ! $this->_outro)
            $html [] = $this->_anchor( BASE_PATH . '/reset',
                                      $this->_ico('fa-solid fa-trash') . 'Réinitialiser les tirages',
                                      ['onclick' => 'return confirm(\'Êtes-vous sûr ?\');',
                                       'class' => 'btn btn-sm btn-danger reset_loto position-fixed bottom-0 start-0']);

        $html [] = $this->_tag('script', '', ['src' => BASE_PATH . '/assets/bootstrap.bundle.min.js']);
        $html [] = $this->_tag('script', '', ['src' => BASE_PATH . '/assets/masonry.pkgd.min.js']);
        return $this->_tag('footer', implode($html), ['class' => '']);
    }


    protected function _ico(string $fontawesome_class): string
    {
        return $this->_tag('i', '', ['class' => 'me-1 ' . $fontawesome_class]);
    }


    protected function _roundIco(string $round): string
    {
        if ('quine' == $round)
            return $this->_ico('fa-solid fa-minus');

        if ('double_quine' == $round)
            return $this->_ico('fa-solid fa-grip-lines');

        return $this->_ico('fa-solid fa-bars');
    }


    protected function _lotoIco(string $loto_id): string
    {
        return 'enfant' == $loto_id
            ? $this->_ico('fa-solid fa-gamepad')
            : $this->_ico('fa-solid fa-bicycle');
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
        return $this->_tag('img', '', array_merge($attribs, ['src' => $this->_url($url, true)]));
    }


    protected function _url(string $url, bool $reset = false): string
    {
        if ( $reset)
            return BASE_PATH . '/' . $url;

        $new_url = [];
        foreach (array_filter([$this->_loto_id,
                               $this->_partie_id,
                               $this->_round_id,
                               $url])
                 as $id)
            $new_url []= $id;

        return BASE_PATH . '/' . implode('/', array_unique($new_url));
    }
}
