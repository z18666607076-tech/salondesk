<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Booking assistant driver
    |--------------------------------------------------------------------------
    |
    | "fake" parses a few natural-language patterns and proposes a real slot
    | from the booking engine. "laravel" uses the official Laravel AI SDK when
    | OPENAI_API_KEY is set. An empty key always falls back to the fake driver.
    |
    */

    'assistant_driver' => env('AI_BOOKING_DRIVER', 'fake'),

];
