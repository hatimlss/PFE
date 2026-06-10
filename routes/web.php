<?php
// routes/api.php
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\DoctorController;
use App\Http\Controllers\API\ReceptionController;
use App\Http\Controllers\API\PatientController;
use App\Http\Controllers\API\AppointmentController;
use App\Http\Controllers\API\ConsultationController;
use App\Http\Controllers\API\OrdonnanceController;
use App\Http\Controllers\API\PaiementController;
use Illuminate\Support\Facades\Route;
 
// ─── PUBLIC ──────────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);
 
// ─── AUTHENTICATED ────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
 
    // Auth
    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::get('/me',               [AuthController::class, 'me']);
    Route::put('/me/password',      [AuthController::class, 'updatePassword']);
 
    // Dashboard (role-aware)
    Route::get('/dashboard',        [DashboardController::class, 'index']);
 
    // ── DOCTORS (public read, admin write) ───────────────────────────────────
    Route::get('/doctors',                          [DoctorController::class, 'index']);
    Route::get('/doctors/{doctor}',                 [DoctorController::class, 'show']);
    Route::get('/doctors/{doctor}/available-slots', [DoctorController::class, 'availableSlots']);
    Route::get('/doctors/{doctor}/stats',           [DoctorController::class, 'stats']);
 
    Route::middleware('role:admin')->group(function () {
        Route::post('/doctors',           [DoctorController::class, 'store']);
        Route::put('/doctors/{doctor}',   [DoctorController::class, 'update']);
        Route::delete('/doctors/{doctor}',[DoctorController::class, 'destroy']);
    });
 
    // ── RECEPTIONS (admin only for CRUD) ─────────────────────────────────────
    Route::get('/receptions',                       [ReceptionController::class, 'index']);
    Route::get('/receptions/{reception}',           [ReceptionController::class, 'show']);
 
    Route::middleware('role:admin')->group(function () {
        Route::post('/receptions',                  [ReceptionController::class, 'store']);
        Route::put('/receptions/{reception}',       [ReceptionController::class, 'update']);
        Route::delete('/receptions/{reception}',    [ReceptionController::class, 'destroy']);
    });
 
    // ── PATIENTS (admin + reception: CRUD | doctor: read) ────────────────────
    Route::middleware('role:admin,reception,doctor')->group(function () {
        Route::get('/patients',                     [PatientController::class, 'index']);
        Route::get('/patients/{patient}',           [PatientController::class, 'show']);
        Route::get('/patients/{patient}/history',   [PatientController::class, 'history']);
    });
 
    Route::middleware('role:admin,reception')->group(function () {
        Route::post('/patients',                    [PatientController::class, 'store']);
        Route::put('/patients/{patient}',           [PatientController::class, 'update']);
        Route::delete('/patients/{patient}',        [PatientController::class, 'destroy']);
    });
 
    // ── APPOINTMENTS (all roles) ──────────────────────────────────────────────
    Route::get('/appointments',                     [AppointmentController::class, 'index']);
    Route::get('/appointments/today',               [AppointmentController::class, 'today']);
    Route::get('/appointments/calendar',            [AppointmentController::class, 'calendar']);
    Route::get('/appointments/{appointment}',       [AppointmentController::class, 'show']);
 
    Route::middleware('role:admin,reception')->group(function () {
        Route::post('/appointments',                [AppointmentController::class, 'store']);
        Route::put('/appointments/{appointment}',   [AppointmentController::class, 'update']);
        Route::delete('/appointments/{appointment}',[AppointmentController::class, 'destroy']);
    });
 
    // Doctor or reception can update status
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
 
    // ── CONSULTATIONS (doctor writes, others read) ────────────────────────────
    Route::get('/consultations',                    [ConsultationController::class, 'index']);
    Route::get('/consultations/{consultation}',     [ConsultationController::class, 'show']);
 
    Route::middleware('role:admin,doctor')->group(function () {
        Route::post('/consultations',               [ConsultationController::class, 'store']);
        Route::put('/consultations/{consultation}', [ConsultationController::class, 'update']);
    });
 
    // ── ORDONNANCES (doctor writes, others read) ──────────────────────────────
    Route::get('/ordonnances',                      [OrdonnanceController::class, 'index']);
    Route::get('/ordonnances/{ordonnance}',         [OrdonnanceController::class, 'show']);
    Route::get('/ordonnances/{ordonnance}/print',   [OrdonnanceController::class, 'printData']);
 
    Route::middleware('role:admin,doctor')->group(function () {
        Route::post('/ordonnances',                 [OrdonnanceController::class, 'store']);
        Route::put('/ordonnances/{ordonnance}',     [OrdonnanceController::class, 'update']);
        Route::delete('/ordonnances/{ordonnance}',  [OrdonnanceController::class, 'destroy']);
    });
 
    // ── PAIEMENTS (reception + admin) ────────────────────────────────────────
    Route::middleware('role:admin,reception')->group(function () {
        Route::get('/paiements',                    [PaiementController::class, 'index']);
        Route::get('/paiements/stats',              [PaiementController::class, 'stats']);
        Route::get('/paiements/{paiement}',         [PaiementController::class, 'show']);
        Route::post('/paiements',                   [PaiementController::class, 'store']);
        Route::put('/paiements/{paiement}',         [PaiementController::class, 'update']);
        Route::delete('/paiements/{paiement}',      [PaiementController::class, 'destroy']);
    });
});