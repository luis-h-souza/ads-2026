<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

abstract class BaseModel
{
    protected static function db(): PDO
    {
        return Database::connection();
    }
}
