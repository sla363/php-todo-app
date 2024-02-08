<?php

declare(strict_types=1);

namespace TodoApp\Service;

use TodoApp\Attribute\Env;

#[Env(['DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST', 'DB_PORT'])]
class EntityManager extends Manager
{
    private string $dbName;
    private string $dbUser;
    private string $dbPassword;
    private string $dbHost;
    private string $dbPort;

    public function init(
        string $dbName,
        string $dbUser,
        string $dbPassword,
        string $dbHost,
        string $dbPort,
    ): void {
        $this->dbName = $dbName;
        $this->dbPassword = $dbPassword;
        $this->dbUser = $dbUser;
        $this->dbHost = $dbHost;
        $this->dbPort = $dbPort;
    }

    /**
     * @return array<string, string>|null
     */
    public function find(string $tableName, string|int $id): ?array
    {
        $pdo = new \PDO(
            'pgsql:host='.$this->dbHost.';port='.$this->dbPort.';dbname='.$this->dbName,
            $this->dbUser,
            $this->dbPassword
        );
        $query = $pdo->query('SELECT * FROM app_task where id = \''.$id.'\'');
        $result = null;

        if ($query !== false) {
            /** @var ?array<string, string> $result */
            $result = $query->fetch(\PDO::FETCH_ASSOC);
        }

        return $result ?: null;
    }
}