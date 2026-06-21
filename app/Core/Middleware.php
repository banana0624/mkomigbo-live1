<?php

namespace App\Core;

class Middleware
{
    public static function handle(array $stack, Request $request, callable $next)
    {
        $pipeline = array_reduce(
            array_reverse($stack),
            fn($next, $mw) => fn($req) => $mw($req, $next),
            $next
        );

        return $pipeline($request);
    }
}