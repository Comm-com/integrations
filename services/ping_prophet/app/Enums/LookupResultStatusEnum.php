<?php

namespace App\Enums;

enum LookupResultStatusEnum: int
{
    case success = 1;
    case parse_error = 100;
    case bad_response_code = 101;
    case request_error = 102;
}
