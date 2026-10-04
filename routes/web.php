<?php

use App\Livewire\PublicBooking;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/book/{tenant:slug}', PublicBooking::class)->name('booking.show');
