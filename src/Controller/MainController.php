<?php

declare(strict_types=1);

namespace TodoApp\Controller;

use TodoApp\Attribute\Route;

class MainController extends Controller
{
    #[Route('/')]
    public function showMain(): void
    {
        self::render('../templates/main.html.sla', [
            'my_name'    => 'John',
            'my_surname' => 'Doe',
            'time'       => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/asdf')]
    public function showAsdf(): void
    {
        echo 'hello asdf!';
    }
}