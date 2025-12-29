<?php

use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Auth\VerificationCodeController;
use App\Http\Controllers\CallbackController;
use App\Http\Controllers\MakingAnAppointmentController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPasswordController;
use Illuminate\Support\Facades\Route;


Route::get('review-filter', App\Http\Controllers\Api\ReviewController::class);
Route::get('doctors/{doctor:ulid}', DoctorController::class);
Route::get('schedule', ScheduleController::class);

Route::post('/making-an-appointment', MakingAnAppointmentController::class);
Route::post('/callback', CallbackController::class);
Route::post('/review', App\Http\Controllers\Review\ReviewController::class);
Route::post('/send-verification-code', VerificationCodeController::class);

Route::put('user', [UserController::class, 'update']);
Route::put('user/reset-password', UserPasswordController::class);
