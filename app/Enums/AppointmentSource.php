<?php

namespace App\Enums;

enum AppointmentSource: string
{
    case Api = 'api';
    case Admin = 'admin';
    case Ai = 'ai';
}
