<?php

use App\Events\NewPromotion;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Route::get('/attachments/{attachment}', [AttachmentController::class, 'show']);
// Route::get('/', function () {
//     return 'hola mundo!';
// });

Route::get('/new-promotion', function () {
    return 'hola mundo!'; //NewPromotion::dispatch('Nueva Promoción', 'Esto esta de pelos!');
});
Route::get('/storage/attachments/archivo.pdf', function () {
    return response()->file(public_path('storage/attachments/86xsHXUSShuwBpHpKNmQPI8C2vf0HwRsmuEteIbk.pdf'));
});
