<?php

namespace MmoAndFriends\LaravelTextFlags\Parsers;

use MmoAndFriends\LaravelTextFlags\TextFlags;

interface ParserContract{

    function __construct(TextFlags &$handler);
    function buildRegExp();
    function match($subject);
    function apply();
    function reset();
    function unsetAllOf($key);
}