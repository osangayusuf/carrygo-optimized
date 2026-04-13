<?php

namespace App\Enums;

enum BidStatus: int
{
    case Upcoming = 0;
    case Live = 1;
    case Closed = 2;
}
