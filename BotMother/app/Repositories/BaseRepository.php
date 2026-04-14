<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

abstract class BaseRepository
{
    public function __construct(protected readonly Database $database)
    {
    }

    protected function pdo(): PDO
    {
        return $this->database->pdo();
    }
}
