<?php

namespace App\Enums;

enum BuildingType: string
{
    case Office = 'office';
    case Residential = 'residential';
    case Retail = 'retail';
    case Industrial = 'industrial';
    case MixedUse = 'mixed_use';
}
