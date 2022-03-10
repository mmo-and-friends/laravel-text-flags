<?php

namespace MmoAndFriends\LaravelTextFlags\Compilers;

use MmoAndFriends\LaravelTextFlags\TextFlags;

interface ParserContract{

    function __construct(TextFlags &$handler);
    function buildRegExp();
    function match($subject);
    function apply();
    function reset();
    function unsetAllOf($key);
}