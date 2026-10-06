<?php

declare(strict_types=1);

namespace Tests\Application\Library;

use App\Application\Library\SortedDonations;
use Tests\TestCase;

class SortedDonationsTest extends TestCase
{
    protected SortedDonations $donations;


    protected function setUp(): void
    {
        parent::setUp();
        $this->donations = SortedDonations::getInstance();
    }


    /** @return string[] */
    protected function _names(array $donations): array
    {
        return array_values(array_map(fn($row) => $row[0], $donations));
    }


    public function testGetLotos(): void
    {
        $this->assertSame(['adulte', 'enfant'], array_values($this->donations->getLotos()));
    }


    public function testGetPartiesInLoto(): void
    {
        $this->assertSame(
            ['Spectacle',
                           'Partie Adulte n°1',
                           'Partie Adulte n°2',
                           'Entracte',
                           'Surprise rouge',
                           'Pas de bol',
                           'Outro',
                           'Gros lot'],
            $this->_names($this->donations->getPartiesIn('adulte'))
        );

        $this->assertSame(
            ['Partie Enfant n°1', 'Gros lot'],
            $this->_names($this->donations->getPartiesIn('enfant'))
        );
    }


    public function testGetPartiesWithoutLotoIsEmpty(): void
    {
        $this->assertSame([], $this->donations->getPartiesIn(''));
    }


    public function testDonatorsOfRoundAreSortedByName(): void
    {
        $this->assertSame(
            ['boulangerie du coin', 'PIZZERIA CHEZ TOTO'],
            $this->_names($this->donations->donators('adulte', '1', 'quine'))
        );
    }


    public function testDonatorsOfDoubleQuine(): void
    {
        $this->assertSame(
            ['Librairie'],
            $this->_names($this->donations->donators('adulte', '2', 'double_quine'))
        );
    }


    public function testDonatorsOfSpecialParties(): void
    {
        $this->assertSame(
            ['Salon de coiffure'],
            $this->_names($this->donations->donators('adulte', 'surprise_rouge', 'carton'))
        );
        $this->assertSame(
            ['Parc aquatique'],
            $this->_names($this->donations->donators('adulte', 'gros_lot', 'carton'))
        );
        $this->assertSame(
            ['Console'],
            $this->_names($this->donations->donators('enfant', 'gros_lot', 'carton'))
        );
    }


    public function testDonatorsWithoutLotoAreUniqueByLogo(): void
    {
        $names = $this->_names($this->donations->donators());

        $this->assertSame(1, count(array_keys($names, 'Magasin de jouets')));
        $this->assertContains('Garage Auto', $names);
        $this->assertNotContains('Lot non trié', $names);
    }


    public function testPrizeSheetPathComesFromEnvironment(): void
    {
        $file = $this->drawsDir . '/other.csv';
        file_put_contents($file, "Parties Seniors,,,,,,\nPartie Seniors n°1,,,,,,\n");
        putenv('LOTOQUEST_LOTO_CSV=' . $file);
        $_ENV['LOTOQUEST_LOTO_CSV'] = $file;
        SortedDonations::reset();

        try {
            $this->assertSame(['seniors'], array_values(SortedDonations::getInstance()->getLotos()));
        } finally {
            $_ENV['LOTOQUEST_LOTO_CSV'] = dirname(__DIR__, 2) . '/fixtures/loto.csv';
            putenv('LOTOQUEST_LOTO_CSV=' . $_ENV['LOTOQUEST_LOTO_CSV']);
        }
    }
}
