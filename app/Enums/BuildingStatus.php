<?php

namespace App\Enums;

enum BuildingStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case UnderRenovation = 'under_renovation';
}
