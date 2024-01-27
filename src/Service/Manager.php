<?php

declare(strict_types=1);

namespace TodoApp\Service;

abstract class Manager
{
    private static Manager $instance;

    protected function __construct()
    {
    }

    public static function getInstance(): static
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}