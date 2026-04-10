<?php

use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\BookingDoctorBranchesAvailabilityController;
use App\Http\Controllers\Api\BookingClinicBranchesController;
use App\Http\Controllers\Api\BookingCityDoctorsByDateCalendarController;
use App\Http\Controllers\Api\BookingCityDoctorsByDateController;
use App\Http\Controllers\Api\Integrations\ServiceIntegrationController;
use App\Http\Controllers\Api\BookingDoctorsController;
use App\Http\Controllers\Auth\VerificationCodeController;
use App\Http\Controllers\CallbackController;
use App\Http\Controllers\MakingAnAppointmentController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPasswordController;
use Illuminate\Support\Facades\Route;


Route::get('review-filter', App\Http\Controllers\Api\ReviewController::class);
Route::get('doctors/{doctor:ulid}', DoctorController::class);
Route::get('booking/doctors', BookingDoctorsController::class);
Route::get('booking/doctors/{doctor}/branches-availability', BookingDoctorBranchesAvailabilityController::class);
Route::get('booking/cities/{city}/doctors-by-date', BookingCityDoctorsByDateController::class);
Route::get('booking/cities/{city}/doctors-by-date/calendar', BookingCityDoctorsByDateCalendarController::class);
Route::get('booking/clinics/{clinic}/branches', BookingClinicBranchesController::class);
Route::get('schedule', ScheduleController::class);

Route::post('/making-an-appointment', MakingAnAppointmentController::class);
Route::post('/callback', CallbackController::class);
Route::post('/review', App\Http\Controllers\Review\ReviewController::class);
Route::post('/send-verification-code', VerificationCodeController::class);

Route::put('user', [UserController::class, 'update']);
Route::put('user/reset-password', UserPasswordController::class);

Route::prefix('integrations/services')
    ->middleware(['services.integration', 'services.integration.city'])
    ->group(function () {
        Route::get('tree', [ServiceIntegrationController::class, 'tree']);
        Route::get('parents', [ServiceIntegrationController::class, 'parents']);
        Route::get('search', [ServiceIntegrationController::class, 'search']);
        Route::get('children-by-title', [ServiceIntegrationController::class, 'childrenByTitle']);
        Route::post('preview', [ServiceIntegrationController::class, 'preview']);
        Route::get('{uuid}/children', [ServiceIntegrationController::class, 'children']);
        Route::post('apply', [ServiceIntegrationController::class, 'apply']);
        Route::get('{uuid}', [ServiceIntegrationController::class, 'show']);
    });
