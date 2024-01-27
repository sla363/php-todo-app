<?php

namespace TodoApp\Entity;

use TodoApp\Utils\Uuid;

class Task
{
    private string $id;

    private string $name = 'New task';

    private ?string $content = null;

    private ?\DateTime $deadline = null;

    public function __construct(
    )
    {
        $this->id = Uuid::generateV4();
    }
}