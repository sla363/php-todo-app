<?php

declare(strict_types=1);

namespace TodoApp\Controller;

class MainController extends Controller
{
    /**
     * @var array<string, string>
     */
    public const array ROUTES = [
        '/'     => 'showMain',
        '/asdf' => 'showAsdf',
    ];

    public function showMain(): void
    {
        self::render('../templates/main.html.sla', [
            'my_name'    => 'John',
            'my_surname' => 'Doe',
            'time'       => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    public function showAsdf(): void
    {
        echo 'hello asdf!';
    }
}