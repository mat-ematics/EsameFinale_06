<?php 

namespace App\Enums;

enum StateEnum: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Banned = 'banned';
    case Locked = 'locked';
}