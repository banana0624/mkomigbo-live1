<?php

require_once __DIR__ . '/../../../../03-runtime-kernel/security/Csrf.php';
require_once __DIR__ . '/../../../../03-runtime-kernel/middleware/GuestMiddleware.php';

class LoginPageController
{
    public function index()
    {
        GuestMiddleware::handle();
        
        echo '
        <html>
        <head>
            <title>Login</title>
        </head>
        <body>

            <h1>Login</h1>

            <form method="POST" action="/public/index.php/web-login">

                <p>
                    <input
                        type="email"
                        name="email"
                        placeholder="Email"
                        required
                    >
                </p>

                <p>
                    <input
                        type="password"
                        name="password"
                        placeholder="Password"
                        required
                    >
                </p>

                <p>
                    <button type="submit">
                        Login
                    </button>
                </p>
                
                <p>
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="' . Csrf::token() . '"
                    >
                </p>
                
            </form>

        </body>
        </html>
        ';
    }
}