<?php

class View
{
    public static function render($title, $content)
    {
        echo '
        <html>
        <head>
            <title>' . htmlspecialchars($title) . '</title>

            <style>

                body {
                    font-family: Arial, sans-serif;
                    margin: 40px;
                }

                nav a {
                    margin-right: 15px;
                }

            </style>

        </head>

        <body>

            <nav>

                <a href="/public/index.php/dashboard">
                    Dashboard
                </a>

                <a href="/public/index.php/admin">
                    Admin
                </a>

                <a href="/public/index.php/api/logout">
                    Logout
                </a>

            </nav>

            <hr>

            ' . $content . '

        </body>
        </html>
        ';
    }
}