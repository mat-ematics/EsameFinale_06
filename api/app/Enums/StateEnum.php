<?php 

namespace App\Enums;

/**
 * L'Enum per gli stati utente
 */
enum StateEnum: string
{
    /** Attivo */
    case Active = 'active';
    /** Sospeso */
    case Suspended = 'suspended';
    /** Bannato */
    case Banned = 'banned';
    /** Bloccato */
    case Locked = 'locked';
}