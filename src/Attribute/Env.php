<?php

declare(strict_types=1);

namespace TodoApp\Attribute;

#[\Attribute]
class Env
{
    /**
     * @param array<int, string> $variables
     */
    public function __construct(
        public array $variables
    ) {
    }
}