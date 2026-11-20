<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DuplicateController;
use App\Http\Controllers\FacadeAuthorizationController;
use App\Http\Controllers\FacadeRequestController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\MyQuotationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Pública, sin sesión: el autorizador llega aquí desde el correo, con el
// token como única credencial. Debe registrarse antes que las rutas
// autenticadas de /fachadas/{facadeRequest} para que "autorizar" no se
// interprete como un ID de solicitud.
Route::get('/fachadas/autorizar/{token}', [FacadeAuthorizationController::class, 'show'])
    ->name('facades.authorization.show');
Route::get('/fachadas/autorizar/{token}/pdf', [FacadeAuthorizationController::class, 'downloadPdf'])
    ->name('facades.authorization.pdf');
Route::post('/fachadas/autorizar/{token}/aprobar', [FacadeAuthorizationController::class, 'approve'])
    ->name('facades.authorization.approve');
Route::post('/fachadas/autorizar/{token}/rechazar', [FacadeAuthorizationController::class, 'reject'])
    ->name('facades.authorization.reject');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::middleware('permission:quotations.view.all|quotations.view.search')->group(function () {
        Route::get('/cotizaciones', [QuotationController::class, 'index'])->name('quotations.index');
        Route::get('/cotizaciones/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
    });

    Route::post('/cotizaciones/{quotation}/asignacion', [AssignmentController::class, 'store'])
        ->middleware('permission:quotations.assign|quotations.reassign')
        ->name('assignments.store');

    Route::post('/cotizaciones/{quotation}/seguimiento', [FollowUpController::class, 'store'])
        ->middleware('permission:quotations.add.followup')
        ->name('follow-ups.store');

    Route::get('/asignaciones', [AssignmentController::class, 'index'])
        ->middleware('permission:quotations.assign')
        ->name('assignments.index');

    Route::post('/asignaciones/auto', [AssignmentController::class, 'autoAssign'])
        ->middleware('permission:quotations.assign')
        ->name('assignments.auto');

    Route::get('/mis-cotizaciones', [MyQuotationController::class, 'index'])
        ->middleware('permission:quotations.view.assigned')
        ->name('my-quotations.index');

    Route::get('/duplicados', [DuplicateController::class, 'index'])
        ->middleware('permission:duplicates.view')
        ->name('duplicates.index');

    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reportes', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reportes/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('/reportes/vendedoras/{user}', [ReportController::class, 'vendor'])->name('reports.vendor');
        Route::get('/reportes/vendedoras/{user}/pdf', [ReportController::class, 'vendorPdf'])->name('reports.vendor.pdf');
    });

    Route::middleware('permission:facades.create')->group(function () {
        Route::get('/fachadas/crear', [FacadeRequestController::class, 'create'])->name('facades.create');
        Route::post('/fachadas', [FacadeRequestController::class, 'store'])->name('facades.store');
    });

    Route::middleware('permission:facades.view|facades.view.all')->group(function () {
        Route::get('/fachadas', [FacadeRequestController::class, 'index'])->name('facades.index');
        Route::get('/fachadas/{facadeRequest}', [FacadeRequestController::class, 'show'])->name('facades.show');
        Route::get('/fachadas/{facadeRequest}/fotos/{photo}', [FacadeRequestController::class, 'photo'])->name('facades.photos.show');

        Route::get('/fachadas/{facadeRequest}/firmas/{type}', [FacadeRequestController::class, 'signature'])
            ->where('type', 'client|agent')
            ->name('facades.signature.show');
    });

    Route::get('/fachadas/{facadeRequest}/pdf', [FacadeRequestController::class, 'downloadPdf'])
        ->middleware('permission:facades.download_pdf')
        ->name('facades.pdf');

    Route::post('/fachadas/{facadeRequest}/enviar-autorizacion', [FacadeRequestController::class, 'sendAuthorizationEmail'])
        ->middleware('permission:facades.authorize')
        ->name('facades.send-authorization');

    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::get('/usuarios/crear', [UserController::class, 'create'])->name('users.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
    });

    Route::get('/notificaciones/{quotationNotification}/ir', [NotificationController::class, 'open'])
        ->name('notifications.open');

    Route::post('/notificaciones/marcar-todas', [NotificationController::class, 'markAllRead'])
        ->name('notifications.mark-all-read');
});

require __DIR__.'/auth.php';
