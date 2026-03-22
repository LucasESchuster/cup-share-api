<?php

namespace App\Enums;

enum EquipmentType: string
{
    case Grinder = 'grinder';
    case EspressoMachine = 'espresso_machine';
    case Scale = 'scale';
    case Dripper = 'dripper';
    case Kettle = 'kettle';
    case Other = 'other';
}
