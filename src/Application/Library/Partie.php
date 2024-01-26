<?php

declare(strict_types=1);

namespace App\Application\Library;

class Partie
{

    protected array $_params = [];
    protected string $_id;


    public function __construct(array $params)
    {
        $this->_params = $params;
    }


    protected function _extractId(): string
    {
        $id = strtolower(trim($this->_params[0]));
        if ($number = (string) filter_var($id, FILTER_SANITIZE_NUMBER_INT))
            return $number;

        if ($prix = (int) $this->_params[2] ?? 0)
            $id .= ' d ' . $prix;

        if ($minutes = $this->_params[3] ?? '')
            $id .= ' ' . $minutes;

        return str_replace(' ', '_', $id);
    }


    public function getMinutes(): int
    {
        return (int) $this->_params[3] ?? 0;
    }


    public function getPrix(): int
    {
        return (int) $this->_params[2] ?? 0;
    }


    public function getId(): string
    {
        return $this->_id ??= $this->_extractId();
    }


    public function getAnchorLabel(): string
    {
        $id = strtolower(trim($this->_params[0]));
        if ( $number = (string) filter_var($id, FILTER_SANITIZE_NUMBER_INT))
        {
            $id_array = explode(' ', $id);
            unset($id_array[1]);
            return implode(' ', $id_array);
        }

        if ($minutes = $this->_params[3] ?? '')
            $id .= ' ' . $minutes . (' minutes');

        return $id;
    }
}
