<?php

use App\Http\Controllers\API\AIController;
use App\Http\Controllers\API\Driver\AuthController as DriverAuthController;
use App\Http\Controllers\API\Driver\TripController as DriverTripController;
use App\Http\Controllers\API\Driver\AssignmentController as DriverAssignmentController;
use App\Http\Controllers\API\Driver\LocationController as DriverLocationController;
use App\Http\Controllers\API\Driver\FuelReceiptController as DriverFuelReceiptController;
use App\Http\Controllers\API\Driver\PodController as DriverPodController;
use App\Http\Controllers\API\Driver\IncidentController as DriverIncidentController;
use App\Http\Controllers\API\Driver\PerformanceController as DriverPerformanceController;
use App\Http\Controllers\API\Driver\AiFeedbackController as DriverAiFeedbackController;
use App\Http\Controllers\API\Driver\AlertController as DriverAlertController;
use App\Http\Controllers\API\Driver\PaymentController as DriverPaymentController;
use App\Http\Controllers\API\Driver\ChatController as DriverChatController;
use App\Http\Controllers\API\Driver\PreTripInspectionController as DriverPreTripInspectionController;
use App\Http\Controllers\API\Driver\EmergencyController as DriverEmergencyController;
use App\Http\Controllers\API\Driver\AnnouncementController as DriverAnnouncementController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\API\MpesaController as ApiMpesaController;
use App\Http\Controllers\API\MpesaCallbackController;
use App\Http\Controllers\API\ReportingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/mpesa/pay', [MpesaController::class, 'pay']);

Route::prefix('payments')->group(function () {
    Route::post('/mpesa/stk-push', [ApiMpesaController::class, 'stkPush']);
    Route::post('/mpesa/callback', [MpesaCallbackController::class, 'handle'])->name('api.payments.mpesa.callback');
    
    // Paystack Webhooks
    Route::post('/paystack/webhook', [\App\Http\Controllers\PaystackWebhookController::class, 'handle'])->name('webhooks.paystack');
});

Route::get('/transactions', [ReportingController::class, 'transactions']);
Route::get('/settlements', [ReportingController::class, 'settlements']);

Route::middleware(['auth:sanctum', 'throttle:10,1'])->group(function () {
    
    // AI Assistant Routes
    Route::prefix('ai')->group(function () {
        // Ask AI a question
        Route::post('/ask', [AIController::class, 'ask'])->middleware('feature:ai_assistant');
        
        // Get AI request result
        Route::get('/result', [AIController::class, 'getResult']);
        
        // AI Logs (Admin only)
        Route::middleware(['can:viewLogs,App\Policies\AIPolicy'])->group(function () {
            Route::get('/logs', [AIController::class, 'logs']);
            Route::get('/logs/{id}', [AIController::class, 'logDetail']);
            Route::get('/stats', [AIController::class, 'stats']);
            Route::delete('/logs', [AIController::class, 'deleteLogs']);
        });
    });

});

Route::prefix('driver')->group(function () {
    Route::post('/login', [DriverAuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [DriverAuthController::class, 'logout']);
        Route::get('/profile', [DriverAuthController::class, 'profile']);
        Route::get('/announcements', [DriverAnnouncementController::class, 'index']);
        Route::post('/announcements/{announcement}/read', [DriverAnnouncementController::class, 'markRead']);
// Trips
Route::get('/trips', [DriverTripController::class, 'index']);
Route::get('/trips/{trip}', [DriverTripController::class, 'show']);

Route::post('/assignments/{assignment}/accept', [DriverAssignmentController::class, 'accept']);
Route::post('/assignments/{assignment}/decline', [DriverAssignmentController::class, 'decline']);

        Route::post('/locations', [DriverLocationController::class, 'store']);
        Route::get('/fuel-receipts', [DriverFuelReceiptController::class, 'index']);
        Route::post('/fuel-receipts', [DriverFuelReceiptController::class, 'store']);
        Route::get('/pods', [DriverPodController::class, 'index']);
        Route::post('/pods', [DriverPodController::class, 'store']);
        Route::post('/incidents', [DriverIncidentController::class, 'store']);

        Route::get('/tyres', [\App\Http\Controllers\API\Driver\TyreController::class, 'index']);
        Route::post('/tyres/install', [\App\Http\Controllers\API\Driver\TyreController::class, 'install']);
        Route::post('/tyres/remove', [\App\Http\Controllers\API\Driver\TyreController::class, 'remove']);
        Route::post('/tyres/discard', [\App\Http\Controllers\API\Driver\TyreController::class, 'discard']);

        Route::get('/performance', [DriverPerformanceController::class, 'show']);
        Route::get('/ai-feedback', [DriverAiFeedbackController::class, 'show']);
        Route::get('/alerts', [DriverAlertController::class, 'index']);
        Route::get('/payments/summary', [DriverPaymentController::class, 'summary']);
        Route::post('/payments/withdraw', [DriverPaymentController::class, 'withdraw']);
        Route::get('/payments/payslip', [DriverPaymentController::class, 'downloadPayslip']);
        Route::get('/chat/unread-count', [DriverChatController::class, 'unreadCount']);
        Route::get('/chat/messages', [DriverChatController::class, 'index']);
        Route::post('/chat/messages', [DriverChatController::class, 'store']);
        Route::post('/chat/messages/{message}/edit', [DriverChatController::class, 'edit']);
        Route::post('/chat/messages/{message}/delete', [DriverChatController::class, 'delete']);

        Route::get('/pre-trip-inspections/latest', [DriverPreTripInspectionController::class, 'latest']);
        Route::post('/pre-trip-inspections', [DriverPreTripInspectionController::class, 'store']);
        Route::post('/emergency/sos', [DriverEmergencyController::class, 'sos']);
    });
});
