<?php

namespace app\Enums;

enum EventTypeEnum: string
{
    case mnp_response = 'mnp/response';
    case hlr_response = 'hlr/response';
    case billing_charge = 'billing/charge';
}
