<?php

declare(strict_types=1);

namespace App\Application\Library;

class SortedDonations
{

    protected static SortedDonations $_instance;

    protected array $_sorted_donations_array = [];

    public static function getInstance(): static
    {
        return static::$_instance ??= new static;
    }


    public function __construct()
    {
        $this->_sorted_donations_array =
            array_map('str_getcsv',
                      file(__DIR__ . '/../../../public/assets/loto.csv'));
    }


    public function donators(string $loto_id = '', string $partie_id = '', string $round_id = ''): array
    {

        $donations = $this->_donations($loto_id, $partie_id, $round_id);

        if ( ! $loto_id || 0 === strpos($partie_id, 'entracte'))
            $donations = $this->_uniqueDonators($donations);

        usort($donations, fn($donation_a, $donation_b) => strcasecmp($donation_a[0]??'', $donation_b[0]??''));
        return $donations;
    }


    protected function _uniqueDonators(array $donations): array
    {
        $unique = [];
        foreach($donations as $donation) {
            if ( ! $donation[6] ?? '')
                continue;

            $key = trim(strtolower($donation[6] ?? ''));
            if ( ! isset($unique[$key]))
                $unique [$key] = $donation;
        }

        return $unique;
    }


    protected function _donations(string $loto_id = '', string $partie_id = '', string $round_id = ''): array
    {
        $imgs = [];
        $in_loto = false;
        $in_partie = false;
        $in_round = false;
        $in_entracte = false;

        if ( 0 === strpos($partie_id, 'entracte')) {
            $in_entracte = true;
            $partie_id = '';
        }

        foreach($this->_sorted_donations_array as $row)
        {
            if ( !$row_as_string = trim(strtolower((string) reset($row))))
                continue;

            if ( ! $in_loto && $this->_isForLoto($row_as_string, $loto_id))
            {
                $in_loto = true;
                continue;
            }

            if ( ! $in_partie && $this->_isForPartie($row_as_string, $loto_id, $partie_id, $in_loto))
            {
                $in_partie = true;
                continue;
            }

            if ( $this->_isForRound($row_as_string, $round_id, $in_partie))
            {
                $in_round = true;
                continue;
            }

            if ( $in_round && (0 === strpos($row_as_string, 'mise de')))
                return $imgs;

            if ( $in_round) {
                $imgs [] = $row;
                continue;
            }

            if ( $loto_id
                 && $in_loto
                 && $in_partie
                 && ! $in_entracte
                 && $this->_isNotForNextRound($row_as_string))
                return $imgs;

            if ( $in_loto && $in_partie && !$round_id && ($row[4] ?? ''))
            {
                $imgs [] = $row;
            }
        }

        return $imgs;
    }


    protected function _isNotForNextRound(string $row_as_string): bool
    {
        return (0 === strpos($row_as_string, 'partie '))
            || (0 === strpos($row_as_string, 'gros lot'))
            || (0 === strpos($row_as_string, 'surprise verte'))
            || (0 === strpos($row_as_string, 'surprise bleu'))
            || (0 === strpos($row_as_string, 'surprise rouge'))
            || (0 === strpos($row_as_string, 'entracte'))
            || (0 === strpos($row_as_string, 'pas de bol'));
    }


    protected function _isForRound(string $row_as_string, string $round_id, bool $in_partie): bool
    {
        if ( ! $in_partie)
            return false;

        if ( ! $round_id)
            return false;

        return 0 === strpos($row_as_string, str_replace('_', '-', strtolower($round_id)));
    }


    protected function _isForPartie(string $row_as_string, string $loto_id, string $partie_id, bool $in_partie): bool
    {
        if (!$partie_id)
            return true;

        if (!$in_partie)
            return false;

        if ( in_array($partie_id, ['gros_lot', 'pas_de_bol', 'surprise_bleu', 'surprise_rouge', 'surprise_verte']))
            return 0 === strpos($row_as_string, str_replace('_', ' ', strtolower($partie_id)));

        return 0 === strpos($row_as_string, strtolower('partie ' . $loto_id . ' n°'. $partie_id));
    }


    protected function _isForLoto(string $row_as_string, string $loto_id): bool
    {
        if ( !$loto_id)
            return true;

        return 0 === strpos($row_as_string, strtolower('parties ' . $loto_id));
    }


    public function getLotos(): array
    {
        $lotos = [];
        foreach($this->_sorted_donations_array as $row)
        {
            if ( !$row = strtolower((string) reset($row)))
                continue;

            if ( 0 === strpos($row, strtolower('parties ')))
                $lotos [] = explode(' ', $row)[1] ?? '';

        }
        return array_filter($lotos);
    }


    public function getPartiesIn(string $loto): array
    {

        if( ! $loto)
            return [];

        $parties = [];
        $in_loto = false;

        foreach($this->_sorted_donations_array as $row)
        {
            if ( !$row_as_string = trim(strtolower((string) reset($row))))
                continue;

            if ( 0 === strpos($row_as_string, strtolower('parties ' . $loto)))
            {
                $in_loto = true;
                continue;
            }

            if ( 0 === strpos($row_as_string, 'parties ') && $parties)
                return $parties;

            if ( 0 === strpos($row_as_string, 'partie ') && $in_loto)
            {
                $parties [] = $row;
                continue;
            }

            if ( 0 === strpos($row_as_string, 'surprise') && $in_loto)
            {
                $parties [] = $row;
                continue;
            }

            if ( 0 === strpos($row_as_string, 'entracte') && $in_loto)
            {
                $parties [] = $row;
                continue;
            }

            if ( 0 === strpos($row_as_string, 'pas de bol') && $in_loto)
            {
                $parties [] = $row;
                continue;
            }

            if ( 0 === strpos($row_as_string, 'gros lot') && $in_loto)
            {
                $parties [] = $row;
                continue;
            }
        }

        return $parties;
    }
}
