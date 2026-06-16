<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\OptionController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\TestAttemptController;
use App\Http\Controllers\Api\UserAnswerController;
use App\Http\Controllers\Api\PackageEnrollmentController;
use App\Http\Controllers\Api\PackageTestController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\StimulusController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */


    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | MASTER DATA
    |--------------------------------------------------------------------------
    */
    Route::apiResource('user', UserController::class);
    Route::apiResource('packages', PackageController::class);

    Route::apiResource('tests', TestController::class);

    Route::apiResource('questions', QuestionController::class);

    Route::apiResource('stimuli', StimulusController::class);

    Route::apiResource('options', OptionController::class);


    Route::post(
        '/packages/{package}/tests',
        [PackageTestController::class, 'store']
    );

    Route::delete(
        '/packages/{package}/tests/{test}',
        [PackageTestController::class, 'destroy']
    );

    Route::get(
        '/user-answers/test-attempt/{id}',
        [UserAnswerController::class, 'byTestAttempt']
    );
    /*
    |--------------------------------------------------------------------------
    | PACKAGE TEST
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/packages/{id}/attach-tests',
        [PackageController::class, 'attachTests']
    );

    Route::get(
        '/my-packages',
        [PackageEnrollmentController::class, 'myPackages']
    );

    Route::get(
        '/my-attempts/{packageId}',
        [TestAttemptController::class, 'myAttemptsByPackage']
    );

    /*
    |--------------------------------------------------------------------------
    | PACKAGE ENROLLMENTS
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'package-enrollments',
        PackageEnrollmentController::class
    );

    /*
    |--------------------------------------------------------------------------
    | TEST ATTEMPTS
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'test-attempts',
        TestAttemptController::class
    );

    /*
    |--------------------------------------------------------------------------
    | USER ANSWERS
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'user-answers',
        UserAnswerController::class
    );
});
