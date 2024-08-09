<?php

namespace App\Enums;

enum ApiRequestStatusEnum
{
    case processing;
    case completed;
    case failed;
}
