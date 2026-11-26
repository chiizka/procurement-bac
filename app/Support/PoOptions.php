<?php

namespace App\Support;

/**
 * Dropdown choices for the Purchase Order form.
 * To add or reword an option, edit the lists below. Saved POs keep the text they were saved with.
 */
final class PoOptions
{
    /** @return list<string> */
    public static function places(): array
    {
        return [
            'DOLE Sorsogon Provincial Office, DOLE Bldg., City Hall Complex, Cabid-An, Sorsogon City',
            'DOLE Catanduanes Provincial Office, Llantino Bldg., Brgy. Concepcion, Virac, Catanduanes',
            // The next two addresses were cut off in the spreadsheet screenshot. Please check them.
            'DOLE Camarines Sur Provincial Office, 2F DOLE Bldg., City Hall Compound, Concepcion, Pequeña, Naga City',
            'DOLE Camarines Norte Provincial Office, 2F Tanzo Bldg., National Diversion Rd. Junction, Brgy. Itomang, Talisay, Camarines Norte',
            'DOLE Masbate Provincial Office, 2F Sanchez Bldg. Crossing., Quezon St., Masbate City',
            'DOLE RO5 Building, Doña Aurora St., Old Albay, Legazpi City',
            'DOLE Albay Provincial Office, 4th Floor Ayala Mall, Legazpi City',
        ];
    }

    /** @return list<string> */
    public static function deliveryTerms(): array
    {
        return ['Free Delivery', 'Cash on delivery'];
    }

    /** @return list<string> */
    public static function paymentTerms(): array
    {
        return [
            '2 to 3 weeks from date of complete delivery and acceptance',
            '3 days upon receipt of payment',
            '3 working days after clearing',
        ];
    }

    /** "Thirteen Thousand ... Pesos & 84/100", the wording used on the PO form. */
    public static function amountInWords(string|int|float $amount): string
    {
        $words = AmountInWords::pesos(number_format((float) $amount, 2, '.', ''));

        return preg_replace('/ and (\d{2}\/100) Only$/', ' & $1', $words);
    }
}
