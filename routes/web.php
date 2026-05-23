<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\InstructorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/category/{id}', [CategoryController::class, 'show'])->name('category.show');



Route::middleware('auth')->prefix('booking')->name('booking.')->group(function () {
    Route::get('/{id}/confirm', [BookingController::class, 'confirmPage'])->name('confirm');

    Route::post('/{id}/process', [BookingController::class, 'process'])->name('process');
});


Route::middleware(['auth', 'role:instructor'])->prefix('cabinet')->name('cabinet.')->group(function () {

    Route::get('/', [InstructorController::class, 'index'])->name('index');

    Route::get('/create', [InstructorController::class, 'create'])->name('create');

    Route::post('/store', [InstructorController::class, 'store'])->name('store');

    Route::get('/{id}/edit', [InstructorController::class, 'edit'])->name('edit');

    Route::put('/{id}/update', [InstructorController::class, 'update'])->name('update');
});

Route::delete('/cabinet/{id}', [InstructorController::class, 'destroy'])->name('cabinet.destroy');
