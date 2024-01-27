<?php

namespace TodoApp\Service;

class EntityManager extends Manager
{
    private EntityManager $instance;
    private string $dbName;
    private string $dbUser;
    private string $dbPassword;

    private function __construct()
    {
        parent::__construct();
    }

}