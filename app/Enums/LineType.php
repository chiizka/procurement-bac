<?php

namespace App\Enums;

enum LineType: string
{
    case Item = 'item';
    case LotHeader = 'lot_header';
    case Note = 'note';
}