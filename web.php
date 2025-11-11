<?php

use App\Http\Controllers\ResidentController;
use App\Http\Controllers\MedicalHistoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserApprovalController;
use App\Http\Controllers\Bhw\DashboardController as BhwDashboardController;
use App\Http\Controllers\MedicalStaff\DashboardController as MedicalStaffDashboardController;
use App\Http\Controllers\DashboardController as MainDashboardController;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\PurokController;
use App\Http\Controllers\HouseholdProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// PROTECTED ROUTES (require authentication)
Route::middleware(['auth', 'verified'])->group(function () {

    // Test user_type middleware routes
    Route::get('/test-admin', function () {
        return "Admin access granted! Welcome " . auth()->user()->name;
    })->middleware('user.type:ADMIN');

    Route::get('/test-medical', function () {
        return "Medical staff access granted!";
    })->middleware('user.type:ADMIN,MEDICAL_STAFF');

    Route::get('/test-bhw', function () {
        return "BHW access granted!";
    })->middleware('user.type:ADMIN,MEDICAL_STAFF,BHW'); 

   

    // Common dashboard that redirects based on user type
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        return match($user->user_type) {
            User::ROLE_ADMIN => redirect()->route('admin.dashboard'),
            User::ROLE_MEDICAL_STAFF => redirect()->route('medical.dashboard'),
            User::ROLE_BHW => redirect()->route('bhw.dashboard'), // ✅ UPDATED: FIELD_WORKER to BHW
            // ✅ REMOVED: User::ROLE_PATIENT => redirect()->route('patient.dashboard'),
            default => Inertia::render('Dashboard'),
        };
    })->name('dashboard');

    // ==================== ADMIN ROUTES ====================
    Route::middleware('user.type:ADMIN')->prefix('admin')->name('admin.')->group(function () {
        
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        
        // User approval management
        Route::get('/user-approvals', [UserApprovalController::class, 'pendingUsers'])->name('users.pending');
        Route::post('/users/{user}/approve', [UserApprovalController::class, 'approveUser'])->name('users.approve');
        Route::post('/users/{user}/reject', [UserApprovalController::class, 'rejectUser'])->name('users.reject');

        // User management
        Route::get('/users', function () {
            $users = User::notPending()
                ->with('approver')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return Inertia::render('Admin/Users/Index', [
                'users' => $users,
            ]);
        })->name('users.index');

        // Complete resource route for admin residents
        Route::resource('resident', ResidentController::class);
        
        // Optional: Medical history routes for admin
        Route::prefix('resident/{resident}')->name('resident.')->group(function () {
            Route::get('/medical-history/create', [MedicalHistoryController::class, 'create'])->name('medical-history.create');
            Route::post('/medical-history', [MedicalHistoryController::class, 'store'])->name('medical-history.store');
            Route::get('/medical-history/{medicalHistory}/edit', [MedicalHistoryController::class, 'edit'])->name('medical-history.edit');
            Route::put('/medical-history/{medicalHistory}', [MedicalHistoryController::class, 'update'])->name('medical-history.update');
            Route::delete('/medical-history/{medicalHistory}', [MedicalHistoryController::class, 'destroy'])->name('medical-history.destroy');
            Route::get('/medical-history', [MedicalHistoryController::class, 'index'])->name('medical-history.index');
        });
    });

    // ==================== MEDICAL STAFF ROUTES ====================
    Route::middleware('user.type:ADMIN,MEDICAL_STAFF')->prefix('medical')->name('medical.')->group(function () {
        
        Route::get('/dashboard', [MedicalStaffDashboardController::class, 'index'])->name('dashboard');

        // Complete resource route for medical staff residents
        Route::resource('resident', ResidentController::class);

        // Medical History routes for Medical Staff
        Route::prefix('resident/{resident}')->name('resident.')->group(function () {
            Route::get('/medical-history/create', [MedicalHistoryController::class, 'create'])->name('medical-history.create');
            Route::post('/medical-history', [MedicalHistoryController::class, 'store'])->name('medical-history.store');
            Route::get('/medical-history/{medicalHistory}/edit', [MedicalHistoryController::class, 'edit'])->name('medical-history.edit');
            Route::put('/medical-history/{medicalHistory}', [MedicalHistoryController::class, 'update'])->name('medical-history.update');
            Route::delete('/medical-history/{medicalHistory}', [MedicalHistoryController::class, 'destroy'])->name('medical-history.destroy');
            Route::get('/medical-history', [MedicalHistoryController::class, 'index'])->name('medical-history.index');
        });
    });

    // ==================== BHW ROUTES ====================
    Route::middleware('user.type:ADMIN,MEDICAL_STAFF,BHW')->prefix('bhw')->name('bhw.')->group(function () { // ✅ UPDATED: FIELD_WORKER to BHW
        
        // Fixed BHW Dashboard - use proper structure
        Route::get('/dashboard', [BhwDashboardController::class, 'index'])->name('dashboard');
        
        Route::get('/scan-qr', function () {
            return Inertia::render('Bhw/ScanQr', [
                'user' => auth()->user()
            ]);
        })->name('scan.qr');

        // QR verification
        Route::post('/scan-qr/verify', function (\Illuminate\Http\Request $request) {
            $qrCode = $request->input('qr_code');
            // ✅ UPDATED: Remove PATIENT QR scanning since PATIENT user type is removed
            // This QR functionality might need to be repurposed or removed
            return back()->with('error', 'QR scanning functionality is currently unavailable.');
        })->name('scan.verify');

        // Complete resource route for BHW residents
        Route::resource('resident', ResidentController::class);

        // Medical History routes nested under resident
        Route::prefix('resident/{resident}')->name('resident.')->group(function () {
            Route::get('/medical-history/create', [MedicalHistoryController::class, 'create'])->name('medical-history.create');
            Route::post('/medical-history', [MedicalHistoryController::class, 'store'])->name('medical-history.store');
            Route::get('/medical-history/{medicalHistory}/edit', [MedicalHistoryController::class, 'edit'])->name('medical-history.edit');
            Route::put('/medical-history/{medicalHistory}', [MedicalHistoryController::class, 'update'])->name('medical-history.update');
            Route::delete('/medical-history/{medicalHistory}', [MedicalHistoryController::class, 'destroy'])->name('medical-history.destroy');
            Route::get('/medical-history', [MedicalHistoryController::class, 'index'])->name('medical-history.index');
        });
    });

    // ==================== HOUSEHOLD MANAGEMENT ROUTES ====================
    Route::middleware(['auth', 'verified'])->prefix('household')->name('household.')->group(function () {
        
        // Barangay Routes - Accessible by ADMIN and MEDICAL_STAFF
        Route::middleware('user.type:ADMIN,MEDICAL_STAFF')->prefix('barangays')->name('barangays.')->group(function () {
            Route::get('/', [BarangayController::class, 'index'])->name('index');
            Route::get('/create', [BarangayController::class, 'create'])->name('create');
            Route::post('/', [BarangayController::class, 'store'])->name('store');
            Route::get('/{barangay}', [BarangayController::class, 'show'])->name('show');
            Route::get('/{barangay}/edit', [BarangayController::class, 'edit'])->name('edit');
             Route::put('/{barangay}', [BarangayController::class, 'update'])->name('update');
            Route::delete('/{barangay}', [BarangayController::class, 'destroy'])->name('destroy');
            
            // Custom barangay routes
            Route::get('/active/list', [BarangayController::class, 'active'])->name('active');
            Route::get('/municipality/{municipality}', [BarangayController::class, 'byMunicipality'])->name('by-municipality');
        });

        // Purok Routes - Accessible by ADMIN, MEDICAL_STAFF, and BHW
        Route::middleware('user.type:ADMIN,MEDICAL_STAFF,BHW')->prefix('puroks')->name('puroks.')->group(function () {
            Route::get('/', [PurokController::class, 'index'])->name('index');
            Route::get('/create', [PurokController::class, 'create'])->name('create');
            Route::post('/', [PurokController::class, 'store'])->name('store');
            Route::get('/{purok}', [PurokController::class, 'show'])->name('show');
            Route::get('/{purok}/edit', [PurokController::class, 'edit'])->name('edit');
            Route::put('/{purok}', [PurokController::class, 'update'])->name('update');
            Route::delete('/{purok}', [PurokController::class, 'destroy'])->name('destroy');
            
            // Custom purok routes
            Route::get('/active/list', [PurokController::class, 'active'])->name('active');
            Route::get('/barangay/{barangayId}', [PurokController::class, 'byBarangay'])->name('by-barangay');
            Route::post('/{id}/increment-count', [PurokController::class, 'incrementHouseholdCount'])->name('increment-count');
        });

        // Household Profile Routes - Accessible by ADMIN, MEDICAL_STAFF, and BHW
        Route::middleware('user.type:ADMIN,MEDICAL_STAFF,BHW')->prefix('profiles')->name('profiles.')->group(function () {
            Route::get('/', [HouseholdProfileController::class, 'index'])->name('index');
            Route::get('/create', [HouseholdProfileController::class, 'create'])->name('create');
            Route::post('/', [HouseholdProfileController::class, 'store'])->name('store');
            Route::get('/{householdProfile}', [HouseholdProfileController::class, 'show'])->name('show');
            Route::get('/{householdProfile}/edit', [HouseholdProfileController::class, 'edit'])->name('edit');
            Route::put('/{householdProfile}', [HouseholdProfileController::class, 'update'])->name('update');
            Route::delete('/{householdProfile}', [HouseholdProfileController::class, 'destroy'])->name('destroy');
            
            // Custom household profile routes
            Route::get('/status/{status}', [HouseholdProfileController::class, 'byNhtsStatus'])->name('by-status');
            Route::get('/complete-visits/list', [HouseholdProfileController::class, 'withCompleteVisits'])->name('complete-visits');
            Route::get('/search/results', [HouseholdProfileController::class, 'search'])->name('search');
        });

    });

    // ==================== COMMON ROUTES (All authenticated users) ====================
    // Route::get('/profile', function () {
    //     return Inertia::render('Profile/Edit', [
    //         'user' => auth()->user()
    //     ]);
    // })->name('profile.edit');

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';