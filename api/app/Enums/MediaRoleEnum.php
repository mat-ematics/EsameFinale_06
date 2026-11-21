<?php
namespace App\Enums;

enum MediaRoleEnum: string
{
    case Cover  = 'cover';    // card image (16:9)
    case Banner = 'banner';   // hero and detail backdrop (optional)
    case Video  = 'video';
}