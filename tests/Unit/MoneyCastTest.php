<?php

namespace Tests\Unit;

use App\Casts\MoneyCast;
use App\Models\Transaction;
use PHPUnit\Framework\TestCase;

class MoneyCastTest extends TestCase
{
    public function test_it_parses_money_strings_to_cents(): void
    {
        $cast = new MoneyCast();
        $model = new Transaction();

        self::assertSame(1, $cast->set($model, 'amount_cents', '0,01', []));
        self::assertSame(1000, $cast->set($model, 'amount_cents', '10', []));
        self::assertSame(1050, $cast->set($model, 'amount_cents', '10.5', []));
        self::assertSame(1050, $cast->set($model, 'amount_cents', '10,50', []));
        self::assertSame(-2033, $cast->set($model, 'amount_cents', '-20,33', []));
    }
}
