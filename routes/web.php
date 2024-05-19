<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('sendemail', function () {

    $data = array(
        'name' => "Dharti Patel",
    );

    Mail::send('emails.test', $data, function ($message) {

        $message->from('dharti2105@gmail.com', 'Hello dharti');

        $message->to('dharatipatel2105@gmail.com')
        ->subject('This is test email');

    });

    return "Your email has been sent successfully";

});
