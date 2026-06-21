<?php

namespace App\Core;

class Response
{
    public static function view(array $page): void
    {
        mk_render_page($page);
    }

    public static function notFound(): void
    {
        http_response_code(404);
        echo "PAGE NOT FOUND";
    }
}