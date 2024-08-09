<?php

namespace app\Enums;

enum ApiRequestStatusEnum
{
    case processing;
    case completed;
    case failed;
}
