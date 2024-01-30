<?php

declare(strict_types=1);

namespace TodoApp\Service;

use TodoApp\Attribute\Env;

#[Env(['DB_NAME', 'DB_USER', 'DB_PASSWORD'])]
class EntityManager extends Manager
{
    private string $dbName;
    private string $dbUser;
    private string $dbPassword;

    public function init(
        string $dbName,
        string $dbUser,
        string $dbPassword,
    ): void {
        $this->dbName = $dbName;
        $this->dbPassword = $dbPassword;
        $this->dbUser = $dbUser;
    }
}