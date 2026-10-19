<?php

use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\CmsAuthController;
use App\Http\Controllers\Api\CmsContentController;
use App\Http\Controllers\Api\CmsUpdateController;
use App\Http\Controllers\Api\CmsUploadController;
use App\Http\Controllers\Api\CmsImageController;
use App\Http\Controllers\Api\LegacyFileController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

$routes = [
    '/api/health', '/api/cms/login', '/api/cms/me', '/api/site/home', '/api/site/contact',
    '/api/site/academics', '/api/site/admissions', '/api/site/why', '/api/site/research-areas',
    '/api/site/research-areas/options?facultyId=1',
    '/api/site/research-areas/results?facultyId=1&departmentId=1&degreeId=1',
    '/api/site/programme-detail?department=Economics', '/api/site/programmes?page=1&search=econ',
    '/api/site/programme-overview?department=Economics', '/api/site/fees',
    '/api/site/international-students', '/api/site/setup-fee', '/api/site/setup-fee/options?facultyId=1',
    '/api/site/intl-fee', '/api/site/intl-fee/calculate?facultyId=1&departmentId=1&degreeId=1&type=New',
    '/api/site/statistics?session=2025/2026',
];

Route::get('/api', fn () => response()->json(['name' => config('app.name'), 'status' => 'ok', 'version' => '1.0.0', 'routes' => $routes]));
Route::get('/api/health', function () {
    DB::connection()->getPdo();
    return response()->json(['status' => 'ok', 'database' => 'connected']);
});

Route::get('/api-external-links.php', [LegacyFileController::class, 'externalLinks']);
Route::get('/api-sectional-heads.php', [LegacyFileController::class, 'sectionalHeads']);
Route::middleware(['web', 'cms.auth'])->group(function () {
    Route::post('/api-external-links.php', [LegacyFileController::class, 'updateExternalLinks']);
    Route::post('/api-sectional-heads.php', [LegacyFileController::class, 'updateSectionalHeads']);
});

Route::prefix('api/site')->controller(SiteController::class)->group(function () {
    Route::get('/home', 'home');
    Route::get('/contact', 'contact');
    Route::get('/academics', 'academics');
    Route::get('/admissions', 'admissions');
    Route::get('/why', 'why');
    Route::get('/research-areas', 'researchAreas');
    Route::get('/research-areas/options', 'researchAreaOptions');
    Route::get('/research-areas/results', 'researchAreaResults');
    Route::get('/programme-detail', 'programmeDetail');
    Route::get('/programmes', 'programmes');
    Route::get('/programme-overview', 'programmeOverview');
    Route::get('/fees', 'fees');
    Route::get('/international-students', 'internationalStudents');
    Route::get('/setup-fee', 'setupFee');
    Route::get('/setup-fee/options', 'setupFeeOptions');
    Route::get('/setup-fee/degrees', 'setupFeeDegrees');
    Route::get('/setup-fee/calculate', 'setupFeeCalculation');
    Route::get('/intl-fee', 'internationalFee');
    Route::get('/intl-fee/calculate', 'internationalFeeCalculation');
    Route::get('/statistics', 'statistics');
});

Route::prefix('api/cms')->middleware('web')->group(function () {
    Route::post('/login', [CmsAuthController::class, 'login']);

    Route::middleware('cms.auth')->group(function () {
        Route::get('/me', [CmsAuthController::class, 'me']);
        Route::post('/logout', [CmsAuthController::class, 'logout']);
        Route::post('/forms/upload', [CmsUploadController::class, 'document']);
        Route::post('/home/carousels', [CmsImageController::class, 'createCarousel']);
        Route::post('/home/carousels/update', [CmsImageController::class, 'updateCarousel']);
        Route::post('/home/carousels/delete', [CmsImageController::class, 'deleteCarousel']);
        Route::post('/home/flagship', [CmsImageController::class, 'createFlagship']);
        Route::post('/home/flagship/update', [CmsImageController::class, 'updateFlagship']);
        Route::post('/home/flagship/delete', [CmsImageController::class, 'deleteFlagship']);
        Route::post('/home/speech/update', [CmsImageController::class, 'provost']);
        Route::post('/academics/image', [CmsImageController::class, 'academicsImage']);
        Route::post('/academics/facilities', [CmsImageController::class, 'createFacility']);
        Route::post('/academics/facilities/update', [CmsImageController::class, 'updateFacility']);
        Route::post('/academics/facilities/delete', [CmsImageController::class, 'deleteFacility']);
        Route::post('/academics/supervisors', [CmsImageController::class, 'createSupervisor']);
        Route::post('/academics/supervisors/update', [CmsImageController::class, 'updateSupervisor']);
        Route::post('/academics/supervisors/delete', [CmsImageController::class, 'deleteSupervisor']);
        Route::post('/academics/students', [CmsImageController::class, 'createStudent']);
        Route::post('/academics/students/update', [CmsImageController::class, 'updateStudent']);
        Route::post('/academics/students/delete', [CmsImageController::class, 'deleteStudent']);

        Route::controller(CmsContentController::class)->group(function () {
            Route::get('/home', 'home');
            Route::get('/home/flagship', 'flagship');
            Route::get('/home/speech', 'speech');
            Route::get('/academics', 'academics');
            Route::get('/academics/programmes', 'programmes');
            Route::get('/academics/programmes/detail', 'programmeDetail');
            Route::get('/academics/facilities', 'facilities');
            Route::get('/academics/supervisors', 'supervisors');
            Route::get('/academics/why', 'why');
            Route::get('/academics/students', 'students');
            Route::get('/academics/fees', 'fees');
            Route::get('/admissions', 'admissions');
            Route::get('/admissions/guide', 'admissionsGuide');
            Route::get('/admissions/after', 'admissionsAfter');
            Route::get('/international', 'international');
            Route::get('/contact', 'contact');
        });

        Route::controller(CmsUpdateController::class)->group(function () {
            Route::post('/academics/content', 'academics');
            Route::post('/academics/programmes', 'createProgramme');
            Route::post('/academics/programmes/update', 'updateProgramme');
            Route::post('/academics/programmes/toggle', 'toggleProgramme');
            Route::post('/academics/why', 'why');
            Route::post('/academics/fees', 'fees');
            Route::post('/admissions', 'admissions');
            Route::post('/admissions/guide', 'admissionsGuide');
            Route::post('/admissions/after', 'admissionsAfter');
            Route::post('/international', 'international');
            Route::post('/contact', 'contact');
        });
    });
});
