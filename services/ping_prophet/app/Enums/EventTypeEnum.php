<?php

namespace App\Enums;

enum EventTypeEnum: string
{
    case mnp_response = 'mnp_response';
    case hlr_response = 'hlr_response';
    case billing_charge = 'billing_charge';
}
