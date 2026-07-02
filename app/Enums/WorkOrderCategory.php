<?php

namespace App\Enums;

enum WorkOrderCategory: string
{
    case Elevator = 'elevator';
    case Plumbing = 'plumbing';
    case Electrical = 'electrical';
    case Hvac = 'hvac';
    case Cleaning = 'cleaning';
    case Security = 'security';
    case Structural = 'structural';
    case General = 'general';
}
