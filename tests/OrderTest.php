<?php

namespace App\Tests;

use App\Entity\Order;

use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function testOrderSetterGetter(): void
    {
        $order = new Order();
        $order->setFromLoc('7.234125;9.239183');
        $order->setDestLoc('1.234561;2.241452');
        $order->setDistance(200.0);
        $order->setPrice(1500.0);

        $this->assertSame('7.234125;9.239183', $order->getFromLoc());
        $this->assertSame('1.234561;2.241452', $order->getDestLoc());
        $this->assertSame(200.0, $order->getDistance());
        $this->assertSame(1500.0, $order->getPrice());
    }
}
