<?php

namespace Tests\Unit;

use App\Support\AmountInWords;
use PHPUnit\Framework\TestCase;

class AmountInWordsTest extends TestCase
{
    public function test_peso_wording_handles_zero_centavos_and_large_amounts(): void
    {
        foreach ([
            '0' => 'Zero Pesos Only', '1' => 'One Peso Only',
            '96000.00' => 'Ninety-Six Thousand Pesos Only',
            '96000.84' => 'Ninety-Six Thousand Pesos and 84/100 Only',
            '0.01' => 'Zero Pesos and 01/100 Only',
            '1000000000' => 'One Billion Pesos Only',
            '1234567.10' => 'One Million Two Hundred Thirty-Four Thousand Five Hundred Sixty-Seven Pesos and 10/100 Only',
        ] as $amount => $words) $this->assertSame($words, AmountInWords::pesos((string) $amount));
    }

    public function test_negative_amounts_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AmountInWords::pesos('-1');
    }
}
