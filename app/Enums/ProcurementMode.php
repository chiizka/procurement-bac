<?php

namespace App\Enums;

enum ProcurementMode: string
{
    case Svp = 'svp';
    case Direct = 'direct';
    case Emergency = 'emergency';
    case AgencyToAgency = 'agency_to_agency';
    case CompetitiveBidding = 'competitive_bidding';
    case PublicBidding = 'public_bidding';
}