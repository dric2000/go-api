<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BecomeDriverController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\FedapayController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SubmitIdentityVerificationController;
use App\Http\Controllers\SubmitVehicleController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    $user = $request->user();

    return array_merge($user->only(['id', 'name', 'email', 'phone']), [
        // Même règle que le middleware hasDriverProfile : profil existant
        // ET approuvé. Évite au front de deviner via un 403 sur une autre route.
        'is_driver' => (bool) ($user->driverProfile && $user->driverProfile->status_verification === 'approved'),
        'identity_status' => $user->identityDocument?->status_verification,
        'license_status' => $user->driverProfile?->status_verification,
    ]);
})->middleware('auth:sanctum');


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Publique : un visiteur non connecté doit pouvoir parcourir les trajets
// disponibles et consulter le détail d'un trajet avant de créer un compte
// (pages "Trajets" du site vitrine). La réservation, elle, reste protégée
// plus bas (elle a besoin de $request->user()).
Route::get('trips', [TripController::class, 'index']);
Route::get('trips/{trip}', [TripController::class, 'show']);
Route::get('trips/{trip}/position', [TripController::class, 'position']);

Route::middleware(['auth:sanctum'])->group(function() {
    Route::put('user', [UserController::class, 'update']);

    Route::post('verify-identity', [SubmitIdentityVerificationController::class, 'store']);
    Route::post('verify-identity/{document}', [SubmitIdentityVerificationController::class, 'update'])->middleware('admin');

    Route::post('become-driver', [BecomeDriverController::class, 'store']);
    Route::post('become-driver/{driverProfile}', [BecomeDriverController::class, 'update'])->middleware('admin');

    Route::get('my-vehicles', [SubmitVehicleController::class, 'index'])->middleware('hasDriverProfile');
    Route::post('add-vehicle', [SubmitVehicleController::class, 'store'])->middleware('hasDriverProfile');
    Route::post('add-vehicle/{vehicle}', [SubmitVehicleController::class, 'update'])->middleware('admin');

    Route::get('my-trips', [TripController::class, 'myTrips'])->middleware('hasDriverProfile');
    Route::post('create-trip', [TripController::class, 'store'])->middleware('canCreateTrip');
    Route::post('trips/{trip}/start', [TripController::class, 'start']);
    Route::post('trips/{trip}/complete', [TripController::class, 'complete']);
    Route::post('trips/{trip}/cancel', [TripController::class, 'cancel']);
    Route::post('trips/{trip}/position', [TripController::class, 'updatePosition']);

    Route::post('trips/{trip}/book', [BookingController::class, 'store']);
    Route::get('trips/{trip}/bookings', [BookingController::class, 'forTrip'])->middleware('hasDriverProfile');
    Route::get('bookings', [BookingController::class, 'index']);
    Route::get('bookings/{booking}', [BookingController::class, 'show']);
    Route::post('bookings/{booking}/confirm-arrival', [TripController::class, 'confirmArrival']);

    Route::get('wallet', [WalletController::class, 'show'])->middleware('hasDriverProfile');
    Route::get('payouts', [PayoutController::class, 'index'])->middleware('hasDriverProfile');
    Route::post('payouts', [PayoutController::class, 'store'])->middleware('hasDriverProfile');
    Route::get('transactions', [TransactionController::class, 'index']);


    Route::post('bookings/{booking}/reviews', [ReviewController::class, 'store']);

    Route::post('bookings/{booking}/disputes', [DisputeController::class, 'store']);
    Route::post('disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->middleware('admin');
});

Route::post('webhooks/fedapay', [FedapayController::class, 'handle']); 
