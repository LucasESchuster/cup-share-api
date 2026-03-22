<?php

namespace App\Enums;

enum BrewMethodCategory: string
{
    case Filter   = 'filter';
    case Espresso = 'espresso';
    case Pressure = 'pressure';
    case ColdBrew = 'cold_brew';
}
