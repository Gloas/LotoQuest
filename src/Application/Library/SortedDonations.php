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
        $imgs = [];
        $in_loto = false;
        $in_partie = false;
        $in_round = false;

        foreach($this->_sorted_donations_array as $row)
        {
            if ( !$row_as_string = trim(strtolower((string) reset($row))))
                continue;

            if ( ! $in_loto && $this->_isForLoto($row_as_string, $loto_id))
            {
                $in_loto = true;
                continue;
            }

            if ( $this->_isForPartie($row_as_string, $loto_id, $partie_id, $in_loto))
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

            if ( $in_loto && $in_partie && $this->_isNotForNextRound($row_as_string))
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
        if (!$in_partie)
            return false;

        if ( in_array($partie_id, ['gros_lot', 'pas_de_bol']))
            return 0 === strpos($row_as_string, str_replace('_', ' ', strtolower($partie_id)));

        return 0 === strpos($row_as_string, strtolower('partie ' . $loto_id . ' n°'. $partie_id));
    }


    protected function _isForLoto(string $row_as_string, string $loto_id): bool
    {
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


    public function getNumberOfPartiesIn(string $loto): int
    {

        if( ! $loto)
            return 0;

        $count = 0;
        $in_loto = false;

        foreach($this->_sorted_donations_array as $row)
        {
            if ( !$row = strtolower((string) reset($row)))
                continue;

            if ( 0 === strpos($row, strtolower('parties ' . $loto)))
            {
                $in_loto = true;
                continue;
            }

            if ( 0 === strpos($row, 'parties ') && $count)
                return $count;

            if ( 0 === strpos($row, 'partie ') && $in_loto)
                $count++;
        }

        return $count;
    }
}
