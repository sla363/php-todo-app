<?php

declare(strict_types=1);

namespace TodoApp\Service;

class EntityManager extends Manager
{
    /** @var array<int, string> */
    public const array REQUIRED_ENV_VARIABLES = [
        'DB_NAME',
        'DB_USER',
        'DB_PASSWORD',
    ];

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