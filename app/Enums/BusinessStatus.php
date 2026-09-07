<?php

namespace App\Enums;

enum BusinessStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Suspended = 'suspended';
    case Rejected = 'rejected';
}
