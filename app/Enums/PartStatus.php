<?php

namespace App\Enums;

enum PartStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Discontinued = 'discontinued';
}
