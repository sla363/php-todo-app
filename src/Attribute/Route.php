<?php

declare(strict_types=1);

namespace TodoApp\Attribute;

#[\Attribute]
class Route
{
    public function __construct(
        public string $name
    ) {
    }
}