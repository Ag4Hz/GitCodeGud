<?php

namespace App\Helpers;

enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
}
