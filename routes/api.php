<?php

// routes/api.php
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\TrainerController;
use Illuminate\Support\Facades\App;

// V1 API Versioning
Route::prefix('v1')->group(function () {

    // login ve register
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'getUser']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);



    // Admin route'lar
    Route::middleware('auth:sanctum')->prefix('admin')->group(function() {
        // Admin  tüm users list
        Route::get('users', [AdminController::class, 'getUsers']);
        // Admin new users add
        Route::post('users', [AdminController::class, 'createUser']);
        // Admin users update
        Route::put('users/{id}', [AdminController::class, 'updateUser']);
        // Admin user delete
        Route::delete('users/{id}', [AdminController::class, 'deleteUser']);
        //user search
        Route::get('users/search',[AdminController::class,'searchUser']);
        //sport add

        Route::post('sports', [SportController::class,'createSport']);
        //sport list
        Route::get('sports',[SportController::class,'getSports']);
        //sport delete
        Route::delete('sports/{id}', [SportController::class,'deleteSport']);

        //Trainer add
        Route::post('trainers', [TrainerController::class,'createTrainer']);
        //Trainer delete
        Route::delete('trainers/{id}', [TrainerController::class,'deleteTrainer']);
        //Trainer list
        Route::get('trainers', [TrainerController::class,'getTrainer']);

        //facility list
        Route::get('facility', [FacilityController::class,'getFacilities']);
        //facility add
        Route::post('facility', [FacilityController::class,'createFacility']);
        //facility update
        Route::put('facility/{id}', [FacilityController::class,'updateFacility']);

        //package add
        Route::post('packages', [PackageController::class,'store']);
        //package update
        Route::put('/packages/{id}', [PackageController::class,'updatePackage']);
        //package delete
        Route::delete('/packages/{id}', [PackageController::class,'deletePackage']);
    });

});

Route::prefix('v1')->group(function() {
    //for see after be login user
    Route::middleware('auth:sanctum')->get('/profile', [CustomerController::class, 'showProfile']);
    //profile update
    Route::middleware('auth:sanctum')->put('/profile', [CustomerController::class,'updateProfile']);
    //profile create
    Route::middleware('auth:sanctum')->post('/profile', [CustomerController::class,'store']);
    //appointment create
    Route::middleware('auth:sanctum')->post('/appointment',[AppointmentController::class,'createAppointments']);
    //customers buy package
    Route::middleware('auth:sanctum')->post('/customer/addPackage', [CustomerController::class,'addPackage']);
});
Route::prefix('v1')->group(function () {
    //package list
    Route::get('packages', [PackageController::class, 'index']);
    //trainer search
    Route::get('trainers/search', [TrainerController::class, 'searchTrainer']);

});

