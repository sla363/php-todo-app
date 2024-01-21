<?php

namespace TodoApp\Controller;

class MainController
{
    public const array ROUTES = [
        '/'     => 'showMain',
        '/asdf' => 'showAsdf',
    ];

    public function showMain(): void
    {
        echo 'hello there!';
    }

    public function showAsdf(): void
    {
        echo 'hello asdf!';
    }
}