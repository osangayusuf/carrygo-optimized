<?php

namespace App\Enums;

enum BidStatus: int
{
    case Live = 0;
    case Disabled = 1;
    case Closed = 2;
}
