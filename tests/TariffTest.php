<?php

namespace App\Tests;

use App\Entity\Tariff;
use PHPUnit\Framework\TestCase;

class TariffTest extends TestCase
{
    public function testTariffSettersGetters(): void
    {
        $tariff = new Tariff();
        
        $tariff->setName('Test tariff');
        $tariff->setBasePrice(150);
        $tariff->setBaseDist(10);
        $tariff->setDistCost(2);

        
        $this->assertSame('Test tariff', $tariff->getName());
        $this->assertSame(150.0, $tariff->getBasePrice());
        $this->assertSame(10.0, $tariff->getBaseDist());
        $this->assertSame(2.0, $tariff->getDistCost());
    }
}
