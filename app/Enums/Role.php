<?php

namespace App\Enums;

enum Role: string
{
    case Bac = 'bac';
    case Procurement = 'procurement';
    case Admin = 'admin';
}
