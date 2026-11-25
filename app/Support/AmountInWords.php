<?php

namespace App\Support;

use InvalidArgumentException;

final class AmountInWords
{
    public static function pesos(string|int|float $amount): string
    {
        $value = (string) $amount;
        if (! preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Expected a non-negative peso amount with at most two decimal places.');
        }
        [$whole, $fraction] = array_pad(explode('.', $value), 2, '');
        $cents = str_pad($fraction, 2, '0');

        return self::integer((int) $whole).' '.((int) $whole === 1 ? 'Peso' : 'Pesos')
            .($cents === '00' ? ' Only' : ' and '.$cents.'/100 Only');
    }

    private static function integer(int $number): string
    {
        $small = ['Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        if ($number < 20) return $small[$number];
        if ($number < 100) {
            $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
            return $tens[intdiv($number, 10)].($number % 10 ? '-'.self::integer($number % 10) : '');
        }
        foreach ([1000000000 => 'Billion', 1000000 => 'Million', 1000 => 'Thousand', 100 => 'Hundred'] as $scale => $label) {
            if ($number >= $scale) {
                return self::integer(intdiv($number, $scale)).' '.$label
                    .($number % $scale ? ' '.self::integer($number % $scale) : '');
            }
        }
        return '';
    }
}
