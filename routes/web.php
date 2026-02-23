<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Doctors\DoctorDashboardController;
use App\Http\Controllers\Doctors\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\CheckupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TreatmentSessionController;
use App\Http\Controllers\SessionInstallmentController;
use App\Http\Controllers\GeneralSettingController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\PaymentOutstandingController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeSalaryController;
use App\Http\Controllers\SessionTimeController;
use App\Http\Controllers\DoctorAvailabilityController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReceptionistDashboardController;
use App\Http\Controllers\PaymentTransactionController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\BankLedgerController;
use App\Http\Controllers\IncomeReportController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\Manager\ManagerDashboardController;
use App\Http\Controllers\ExpenseTypeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\RolePermissionController;
//use App\Http\Controllers\AppointmentController;
//use App\Http\Controllers\EnrollmentController;
//use App\Http\Controllers\PaymentController; 

Auth::routes();

// Clear all cache route
Route::get('/clear', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return "All caches cleared successfully!";
})->name('clear');

// ✅ Admin Dashboard Routes
Route::prefix('admin')
    ->middleware(['auth:web', 'role:admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard'])
            ->middleware('check_user_permission:view_dashboard')
            ->name('dashboard');

            // 🔹 Appointments (Checkups) - FIXED: Using consistent middleware
        Route::get('appointments', [CheckupController::class, 'index'])
            ->middleware('check_user_permission:view appointments')
            ->name('appointments.index');

        Route::get('appointments/create', [CheckupController::class, 'create'])
            ->middleware('check_user_permission:create appointments')
            ->name('appointments.create');

        Route::post('appointments/store', [CheckupController::class, 'store'])
            ->middleware('check_user_permission:create appointments')
            ->name('appointments.store');

    });

// ✅ Manager Dashboard Routes
Route::prefix('manager')
    ->middleware(['auth:web', 'role:manager'])
    ->name('manager.')
    ->group(function () {
        Route::get('dashboard', [ManagerDashboardController::class, 'index'])
            ->middleware('check_user_permission:view_dashboard')
            ->name('dashboard');

             // 🔹 Appointments (Checkups) - FIXED: Using consistent middleware
        Route::get('appointments', [CheckupController::class, 'index'])
            ->middleware('check_user_permission:view appointments')
            ->name('appointments.index');

        Route::get('appointments/create', [CheckupController::class, 'create'])
            ->middleware('check_user_permission:create appointments')
            ->name('appointments.create');

        Route::post('appointments/store', [CheckupController::class, 'store'])
            ->middleware('check_user_permission:create appointments')
            ->name('appointments.store');

    });

// ✅ Doctor Dashboard Routes
Route::prefix('doctor')
    ->middleware(['auth:doctor', 'role:doctor'])
    ->name('doctor.')
    ->group(function () {
        // 🔹 Dashboard
        Route::get('dashboard', [DoctorDashboardController::class, 'index'])
            ->middleware('check_user_permission:view_dashboard')
            ->name('dashboard');

        // 🔹 Doctor Enrollments
        Route::get('enrollments/{status}', [TreatmentSessionController::class, 'showEnrollments'])
            ->middleware('check_user_permission:view enrollment')
            ->name('enrollments.index');
            
        // 🔹 Doctor Consultations
        Route::get('consultations/{status}', [TreatmentSessionController::class, 'index'])
            ->middleware('check_user_permission:view consultation')
            ->name('consultations.index');

        // 🔹 Consultation Status View/Update
        Route::get('consultations/{id}/status-view', [TreatmentSessionController::class, 'viewssStatus'])
            ->middleware('check_user_permission:manage_appointments')
            ->name('consultations.status-view');
        
        Route::post('consultations/update-status', [TreatmentSessionController::class, 'updateStatus'])
            ->middleware('check_user_permission:manage_appointments')
            ->name('consultations.update-status');

        // 🔹 Treatment Session Store
        Route::post('sessions/store', [TreatmentSessionController::class, 'store'])
            ->middleware('check_user_permission:view enrollment')
            ->name('sessions.store');

        // 🔹 Appointments (Checkups) - FIXED: Using consistent middleware
        Route::get('appointments', [CheckupController::class, 'index'])
            ->middleware('check_user_permission:view appointments')
            ->name('appointments.index');

        Route::get('appointments/create', [CheckupController::class, 'create'])
            ->middleware('check_user_permission:create appointments')
            ->name('appointments.create');

        Route::post('appointments/store', [CheckupController::class, 'store'])
            ->middleware('check_user_permission:create appointments')
            ->name('appointments.store');

        // 🔹 Sessions
        Route::get('sessions', [SessionController::class, 'index'])
            ->middleware('check_user_permission:manage_sessions')
            ->name('sessions.index');

        Route::get('ongoing-sessions/{status}', [TreatmentSessionController::class, 'OngoingSessionsOnly'])
            ->middleware('check_user_permission:manage_sessions')
            ->name('ongoing-sessions');

        Route::get('session-details/{id}', [TreatmentSessionController::class, 'sessionDetails'])
            ->middleware('check_user_permission:manage_sessions')
            ->name('session-details');

        Route::post('sessions/mark-completed', [SessionTimeController::class, 'updateSectionCompleted'])
            ->middleware('check_user_permission:manage_sessions')
            ->name('sessions.mark-completed');

        // 🔹 Feedback (View Only)
        Route::get('feedback', [FeedbackController::class, 'index'])
            ->middleware('check_user_permission:view feedback')
            ->name('feedback.index');

        Route::get('feedback/doctor-list', [FeedbackController::class, 'doctorFeedbackList'])
            ->middleware('check_user_permission:view feedback')
            ->name('feedback.doctor-list');

        Route::get('feedback/patient-list', [FeedbackController::class, 'patientFeedbackList'])
            ->middleware('check_user_permission:view feedback')
            ->name('feedback.patient-list');
    });

// ✅ Receptionist Dashboard Routes - FIXED: Added correct middleware and fixed ConsultationController
Route::prefix('receptionist')
    ->middleware(['auth:web', 'role:receptionist'])
    ->name('receptionist.')
    ->group(function () {
        Route::get('dashboard', [ReceptionistDashboardController::class, 'index'])
            ->middleware('check_user_permission:view_dashboard')
            ->name('dashboard');

      // 🔹 Appointments (Checkups) - FIXED: Using consistent middleware
        Route::get('appointments', [CheckupController::class, 'index'])
            ->middleware('check_user_permission:view appointments')
            ->name('appointments.index');

        Route::get('appointments/create', [CheckupController::class, 'create'])
            ->middleware('check_user_permission:create appointments')
            ->name('appointments.create');

        Route::post('appointments/store', [CheckupController::class, 'store'])
            ->middleware('check_user_permission:create appointments')
            ->name('appointments.store');


    
    
        // 🔹 Feedback (view-only)
        Route::get('feedback', [FeedbackController::class, 'index'])
            ->middleware('check_user_permission:view feedback')
            ->name('feedback.index');

    });

// ✅ Shared Routes (for admin, manager, receptionist)
Route::middleware(['auth:web', 'role:admin|manager|receptionist'])
    ->group(function () {

        // ================= PATIENTS =================
        Route::prefix('patients')->group(function () {
            Route::get('/', [PatientController::class, 'index'])
                ->middleware('check_user_permission:view patients')
                ->name('patients.index');
            
            Route::get('/create', [PatientController::class, 'create'])
                ->middleware('check_user_permission:create patients')
                ->name('patients.create');
            
            Route::post('/', [PatientController::class, 'store'])
                ->middleware('check_user_permission:create patients')
                ->name('patients.store');
            
            Route::get('/{id}/edit', [PatientController::class, 'edit'])
                ->middleware('check_user_permission:edit patients')
                ->name('patients.edit');
            
            Route::put('/{id}', [PatientController::class, 'update'])
                ->middleware('check_user_permission:edit patients')
                ->name('patients.update');
            
            Route::get('/{id}', [PatientController::class, 'show'])
                ->middleware('check_user_permission:view patients')
                ->name('patients.card');
            
            Route::delete('/{id}', [PatientController::class, 'destroy'])
                ->middleware('check_user_permission:delete patients')
                ->name('patients.destroy');
        });

        // ================= DOCTORS =================
        Route::prefix('doctors')->group(function () {
            Route::get('/', [DoctorController::class, 'index'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('doctors.index');
            
            Route::get('/create', [DoctorController::class, 'create'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('doctors.create');
            
            Route::post('/store', [DoctorController::class, 'store'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('doctors.store');
            
            Route::get('/{id}/edit', [DoctorController::class, 'edit'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('doctors.edit');
            
            Route::put('/{id}', [DoctorController::class, 'update'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('doctors.update');
            
            Route::get('/{id}', [DoctorController::class, 'show'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('doctors.show');
            
            Route::delete('/{id}', [DoctorController::class, 'destroy'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('doctors.destroy');

            // Doctor Availability
            Route::get('/{doctor}/availability', [DoctorAvailabilityController::class, 'index'])
        ->middleware('check_user_permission:manage_appointments')
        ->name('doctors.availability.index');

    Route::post('/{doctor}/availability/store', [DoctorAvailabilityController::class, 'store'])
        ->middleware('check_user_permission:manage_appointments')
        ->name('doctors.availability.store');

    Route::post('/{doctor}/availability/generate-next-month', [DoctorAvailabilityController::class, 'generateNextMonth'])
        ->middleware('check_user_permission:manage_appointments')
        ->name('doctors.availability.generateNextMonth');

    Route::delete('/{doctor}/availability/delete-month', [DoctorAvailabilityController::class, 'deleteMonth'])
        ->middleware('check_user_permission:manage_appointments')
        ->name('doctors.availability.deleteMonth');
});

        // ================= CHECKUPS/CONSULTATIONS =================
        Route::prefix('consultations')->group(function () {
            Route::get('/', [CheckupController::class, 'index'])
                ->middleware('check_user_permission:view consultation')
                ->name('consultations.index');
            
            Route::get('/create', [CheckupController::class, 'create'])
                ->middleware('check_user_permission:view consultation')
                ->name('consultations.create');
            
            Route::post('/', [CheckupController::class, 'store'])
                ->middleware('check_user_permission:view consultation')
                ->name('consultations.store');
            
            Route::get('/{id}/edit', [CheckupController::class, 'edit'])
                ->middleware('check_user_permission:view consultation')
                ->name('consultations.edit');
            
            Route::put('/{id}', [CheckupController::class, 'update'])
                ->middleware('check_user_permission:view consultation')
                ->name('consultations.update');
            
            Route::delete('/{id}', [CheckupController::class, 'destroy'])
                ->middleware('check_user_permission:view consultation')
                ->name('consultations.destroy');
            
            Route::get('/{id}', [CheckupController::class, 'show'])
                ->middleware('check_user_permission:view consultation')
                ->name('consultations.show');
            
            Route::get('/{id}/print', [CheckupController::class, 'printSlip'])
                ->middleware('check_user_permission:view consultation')
                ->name('consultations.print');
            
            Route::get('/print-custom/{id}', [CheckupController::class, 'printSlipCustom'])
                ->middleware('check_user_permission:view consultation')
                ->name('consultations.print.custom');
            
            Route::get('/history/{patient_id}', [CheckupController::class, 'history'])
                ->middleware('check_user_permission:view consultation')
                ->name('consultations.history');
        });

       

        // ================= TREATMENT SESSIONS =================
        Route::prefix('treatment-sessions')->group(function () {
            Route::get('/', [TreatmentSessionController::class, 'index'])
                ->middleware('check_user_permission:manage_sessions')
                ->name('treatment-sessions.index');
            
            Route::get('/create', [TreatmentSessionController::class, 'create'])
                ->middleware('check_user_permission:create enrollment')
                ->name('treatment-sessions.create');
            
            Route::get('/create/{checkup}', [TreatmentSessionController::class, 'createWithCheckup'])
                ->middleware('check_user_permission:create enrollment')
                ->name('treatment-sessions.createWithCheckup');
            
            Route::post('/', [TreatmentSessionController::class, 'store'])
                ->middleware('check_user_permission:create enrollment')
                ->name('treatment-sessions.store');
            
            Route::get('/{id}/edit', [TreatmentSessionController::class, 'edit'])
                ->middleware('check_user_permission:edit enrollment')
                ->name('treatment-sessions.edit');
            
            Route::put('/{id}', [TreatmentSessionController::class, 'update'])
                ->middleware('check_user_permission:edit enrollment')
                ->name('treatment-sessions.update');
            
            Route::delete('/{id}', [TreatmentSessionController::class, 'destroy'])
                ->middleware('check_user_permission:delete enrollment')
                ->name('treatment-sessions.destroy');
            
            Route::get('/{id}', [TreatmentSessionController::class, 'show'])
                ->middleware('check_user_permission:view enrollment')
                ->name('treatment-sessions.show');
            
            Route::get('/summary', [TreatmentSessionController::class, 'sessionSummary'])
                ->middleware('check_user_permission:view enrollment')
                ->name('treatment-sessions.summary');
            
            Route::get('/sessions/{session_id}', [TreatmentSessionController::class, 'showOngoingSessions'])
                ->middleware('check_user_permission:manage_sessions')
                ->name('treatment-sessions.sessions');
            
            Route::put('/{id}/enrollment-update', [TreatmentSessionController::class, 'enrollmentUpdate'])
                ->middleware('check_user_permission:edit enrollment')
                ->name('treatment-sessions.enrollmentUpdate');
            
            Route::get('/{session_id}/add-entry', [TreatmentSessionController::class, 'addEntryForm'])
                ->middleware('check_user_permission:manage_sessions')
                ->name('treatment-sessions.add-entry');
            
            Route::post('/{session_id}/store-entry', [TreatmentSessionController::class, 'storeEntry'])
                ->middleware('check_user_permission:manage_sessions')
                ->name('treatment-sessions.store-entry');
        });

        // ================= SESSIONS =================
        Route::prefix('sessions')->group(function () {
            Route::get('/', [SessionController::class, 'index'])
                ->middleware('check_user_permission:manage_sessions')
                ->name('sessions.index');
            
            Route::post('/{id}/complete', [SessionTimeController::class, 'markCompleted'])
                ->middleware('check_user_permission:manage_sessions')
                ->name('sessions.complete');
            
            Route::delete('/{id}', [SessionTimeController::class, 'destroy'])
                ->middleware('check_user_permission:delete enrollment')
                ->name('sessions.destroy');
            
            Route::post('/mark-completed', [SessionTimeController::class, 'updateSectionCompleted'])
                ->middleware('check_user_permission:manage_sessions')
                ->name('sessions.mark-completed');
        });

        // ================= ENROLLMENTS =================
        Route::prefix('enrollments')->group(function () {
            Route::get('/{status}', [TreatmentSessionController::class, 'showEnrollments'])
                ->middleware('check_user_permission:view enrollment')
                ->name('enrollments');
        });

        // ================= PAYMENTS & ACCOUNTS =================
        Route::prefix('payments')->group(function () {
            Route::get('/outstanding-invoices', [PaymentOutstandingController::class, 'index'])
                ->middleware('check_user_permission:view payments')
                ->name('accounts.payments');
            
            Route::get('/completed-invoices', [PaymentOutstandingController::class, 'completedInvoices'])
                ->middleware('check_user_permission:view payments')
                ->name('accounts.completed-invoices');
            
            Route::get('/outstandings', [PaymentOutstandingController::class, 'index'])
                ->middleware('check_user_permission:view payments')
                ->name('payments.outstandings');
            
            Route::get('/return-payments', [PaymentOutstandingController::class, 'returnPayments'])
                ->middleware('check_user_permission:view returns')
                ->name('payments.return-payments');
            
            Route::post('/return', [PaymentOutstandingController::class, 'returnPayment'])
                ->middleware('check_user_permission:create returns')
                ->name('payments.returnPayment');
            
            Route::get('/search-patient', [PaymentOutstandingController::class, 'searchPatient'])
                ->middleware('check_user_permission:view payments')
                ->name('payments.search-patient');
            
            Route::get('/fetch-patient-payments', [PaymentOutstandingController::class, 'fetchPatientPayments'])
                ->middleware('check_user_permission:view payments')
                ->name('payments.fetch-patient-payments');
        });

        // ================= INVOICE LEDGER =================
        Route::prefix('invoice')->group(function () {
            Route::get('/ledger/{session_id}', [PaymentOutstandingController::class, 'invoiceLedger'])
                ->middleware('check_user_permission:view payments')
                ->name('invoice.ledger');
            
            Route::get('/patient-invoice-ledger/{session_id}', [PaymentOutstandingController::class, 'invoiceLedgerr'])
                ->middleware('check_user_permission:view payments')
                ->name('invoice.ledgerr');
            
            Route::post('/add-payment', [PaymentOutstandingController::class, 'addPayment'])
                ->middleware('check_user_permission:create payments')
                ->name('invoice.add-payment');
        });

        // ================= CHECKUP PAYMENTS =================
        Route::prefix('checkups')->group(function () {
            Route::get('/invoice/{checkup_id}', [PaymentOutstandingController::class, 'invoiceLedgerCheckup'])
                ->middleware('check_user_permission:view payments')
                ->name('checkups.invoice');
            
            Route::post('/refund', [PaymentOutstandingController::class, 'returnCheckupPayment'])
                ->middleware('check_user_permission:create returns')
                ->name('checkups.refund');
        });

        // ================= PAYMENT TRANSFER =================
        Route::prefix('transfer')->group(function () {
            Route::get('/', [PaymentTransactionController::class, 'index'])
                ->middleware('check_user_permission:manage_payments')
                ->name('transfer.index');
            
            Route::post('/', [PaymentTransactionController::class, 'store'])
                ->middleware('check_user_permission:manage_payments')
                ->name('transfer.store');
            
            Route::get('/get-bank-balance/{id}', [PaymentTransactionController::class, 'getBankBalance'])
                ->middleware('check_user_permission:view payments')
                ->name('transfer.getBankBalance');
            
            Route::get('/get-branch-balance/{id}', [PaymentTransactionController::class, 'getBranchBalance'])
                ->middleware('check_user_permission:view payments')
                ->name('transfer.getBranchBalance');
        });

        // ================= LEDGERS =================
        Route::prefix('ledger')->group(function () {
            Route::get('/', [LedgerController::class, 'index'])
                ->middleware('check_user_permission:view_reports')
                ->name('ledger.index');
            
            Route::get('/filter', [LedgerController::class, 'filter'])
                ->middleware('check_user_permission:view_reports')
                ->name('ledger.filter');
        });

        // ================= BANK LEDGER =================
        Route::prefix('bank-ledger')->group(function () {
            Route::get('/', [BankLedgerController::class, 'index'])
                ->middleware('check_user_permission:view_reports')
                ->name('bankledger.index');
            
            Route::get('/filter', [BankLedgerController::class, 'filter'])
                ->middleware('check_user_permission:view_reports')
                ->name('bankledger.filter');
        });

        // ================= INCOME REPORT =================
        Route::prefix('income-report')->group(function () {
            Route::get('/', [IncomeReportController::class, 'index'])
                ->middleware('check_user_permission:view_reports')
                ->name('income.report');
        });

        // ================= FEEDBACK =================
        Route::prefix('feedback')->group(function () {
            Route::get('/doctor-list', [FeedbackController::class, 'doctorFeedbackList'])
                ->middleware('check_user_permission:view feedback')
                ->name('feedback.doctor-list');
            
            Route::get('/patient-list', [FeedbackController::class, 'patientFeedbackList'])
                ->middleware('check_user_permission:view feedback')
                ->name('feedback.patient-list');
            
            Route::get('/doctor/{sessionId}', [FeedbackController::class, 'doctorFeedbackForm'])
                ->middleware('check_user_permission:view feedback')
                ->name('feedback.doctor');
            
            Route::post('/doctor-submit', [FeedbackController::class, 'doctorFeedbackSubmit'])
                ->middleware('check_user_permission:view feedback')
                ->name('feedback.doctor-submit');
            
            Route::get('/patient/{session_id}', [FeedbackController::class, 'patientFeedbackForm'])
                ->middleware('check_user_permission:view feedback')
                ->name('feedback.patient');
            
            Route::post('/patient-submit', [FeedbackController::class, 'patientFeedbackSubmit'])
                ->middleware('check_user_permission:view feedback')
                ->name('feedback.patient-submit');
        });

        // ================= DOCTOR CONSULTATIONS =================
        Route::prefix('doctor-consultations')->group(function () {
            Route::get('/{status}', [TreatmentSessionController::class, 'index'])
                ->middleware('check_user_permission:view consultation')
                ->name('doctor-consultations.index');
            
            Route::get('/{id}/status-view', [TreatmentSessionController::class, 'viewssStatus'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('doctor-consultations.status-view');
            
            Route::post('/update-status', [TreatmentSessionController::class, 'updateStatus'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('doctor-consultations.update-status');
        });

        // ================= ONGOING SESSIONS =================
        Route::prefix('ongoing-sessions')->group(function () {
            Route::get('/{status}', [TreatmentSessionController::class, 'OngoingSessionsOnly'])
                ->middleware('check_user_permission:manage_sessions')
                ->name('ongoing-sessions');
            
            Route::get('/session-details/{id}', [TreatmentSessionController::class, 'sessionDetails'])
                ->middleware('check_user_permission:manage_sessions')
                ->name('session-details');
        });

        // ================= EMPLOYEES =================
        Route::prefix('employees')->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])
                ->middleware('check_user_permission:view_reports')
                ->name('employees.index');
            
            Route::get('/create', [EmployeeController::class, 'create'])
                ->middleware('check_user_permission:view_reports')
                ->name('employees.create');
            
            Route::post('/', [EmployeeController::class, 'store'])
                ->middleware('check_user_permission:view_reports')
                ->name('employees.store');
            
            Route::get('/{id}/edit', [EmployeeController::class, 'edit'])
                ->middleware('check_user_permission:view_reports')
                ->name('employees.edit');
            
            Route::put('/{id}', [EmployeeController::class, 'update'])
                ->middleware('check_user_permission:view_reports')
                ->name('employees.update');
            
            Route::delete('/{id}', [EmployeeController::class, 'destroy'])
                ->middleware('check_user_permission:view_reports')
                ->name('employees.destroy');
        });

        // ================= SALARIES =================
        Route::prefix('salaries')->group(function () {
            Route::get('/', [EmployeeSalaryController::class, 'index'])
                ->middleware('check_user_permission:view_reports')
                ->name('salaries.index');
            
            Route::get('/create', [EmployeeSalaryController::class, 'create'])
                ->middleware('check_user_permission:manage_payments')
                ->name('salaries.create');
            
            Route::post('/', [EmployeeSalaryController::class, 'store'])
                ->middleware('check_user_permission:manage_payments')
                ->name('salaries.store');
            
            Route::post('/{id}/pay', [EmployeeSalaryController::class, 'markAsPaid'])
                ->middleware('check_user_permission:manage_payments')
                ->name('salaries.pay');
            
            Route::post('/mark-paid', [EmployeeSalaryController::class, 'markPaidWithAdjustment'])
                ->middleware('check_user_permission:manage_payments')
                ->name('salaries.markPaid');
        });

        // ================= INSTALLMENTS =================
        Route::prefix('installments')->group(function () {
            Route::get('/create/{session_id}', [SessionInstallmentController::class, 'create'])
                ->middleware('check_user_permission:create payments')
                ->name('installments.create');
            
            Route::post('/store', [SessionInstallmentController::class, 'store'])
                ->middleware('check_user_permission:create payments')
                ->name('installments.store');
        });

        // ================= SETTINGS =================
        Route::prefix('settings')->group(function () {
            Route::get('/general', [GeneralSettingController::class, 'index'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('settings.index');
            
            Route::post('/general', [GeneralSettingController::class, 'update'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('settings.update');
        });

        Route::prefix('general-settings')->group(function () {
            Route::get('/', [GeneralSettingController::class, 'index'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('general-settings.index');
            
            Route::get('/{id}/edit', [GeneralSettingController::class, 'edit'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('general-settings.edit');
            
            Route::put('/{id}/update', [GeneralSettingController::class, 'update'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('general-settings.update');
        });

        // ================= BRANCHES =================
        Route::prefix('branches')->group(function () {
            Route::get('/', [BranchController::class, 'index'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('branches.index');
            
            Route::get('/create', [BranchController::class, 'create'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('branches.create');
            
            Route::post('/store', [BranchController::class, 'store'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('branches.store');
            
            Route::get('/edit/{id}', [BranchController::class, 'edit'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('branches.edit');
            
            Route::put('/update/{id}', [BranchController::class, 'update'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('branches.update');
            
            Route::delete('/delete/{id}', [BranchController::class, 'destroy'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('branches.destroy');
        });

        // ================= BANKS =================
        Route::prefix('banks')->group(function () {
            Route::get('/', [BankController::class, 'index'])
                ->middleware('check_user_permission:manage_payments')
                ->name('banks.index');
            
            Route::get('/create', [BankController::class, 'create'])
                ->middleware('check_user_permission:manage_payments')
                ->name('banks.create');
            
            Route::post('/', [BankController::class, 'store'])
                ->middleware('check_user_permission:manage_payments')
                ->name('banks.store');
            
            Route::get('/{id}', [BankController::class, 'show'])
                ->middleware('check_user_permission:manage_payments')
                ->name('banks.show');
            
            Route::get('/{id}/edit', [BankController::class, 'edit'])
                ->middleware('check_user_permission:manage_payments')
                ->name('banks.edit');
            
            Route::put('/{id}', [BankController::class, 'update'])
                ->middleware('check_user_permission:manage_payments')
                ->name('banks.update');
            
            Route::delete('/{id}', [BankController::class, 'destroy'])
                ->middleware('check_user_permission:manage_payments')
                ->name('banks.destroy');
        });

        // ================= USERS =================
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('users.index');
            
            Route::get('/create', [UserController::class, 'create'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('users.create');
            
            Route::post('/store', [UserController::class, 'store'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('users.store');
            
            Route::get('/edit/{id}', [UserController::class, 'edit'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('users.edit');
            
            Route::put('/update/{id}', [UserController::class, 'update'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('users.update');
            
            Route::delete('/delete/{id}', [UserController::class, 'destroy'])
                ->middleware('check_user_permission:manage_appointments')
                ->name('users.destroy');

                // Show user permissions page
             Route::get('/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');

             // Update user permissions via AJAX
             Route::post('/permissions/update', [UserController::class, 'updatePermissions'])->name('user.permissions.update');

        });

        // ================= EXPENSE TYPES =================
        Route::prefix('expense-types')->group(function () {
            Route::get('/', [ExpenseTypeController::class, 'index'])
                ->middleware('check_user_permission:manage_payments')
                ->name('expense.types');
            
            Route::post('/store', [ExpenseTypeController::class, 'store'])
                ->middleware('check_user_permission:manage_payments')
                ->name('expense.types.store');
        });

        // ================= EXPENSES =================
        Route::prefix('expenses')->group(function () {
            Route::get('/', [ExpenseController::class, 'index'])
                ->middleware('check_user_permission:view_reports')
                ->name('expenses.index');
            
            Route::get('/create', [ExpenseController::class, 'create'])
                ->middleware('check_user_permission:manage_payments')
                ->name('expenses.create');
            
            Route::post('/store', [ExpenseController::class, 'store'])
                ->middleware('check_user_permission:manage_payments')
                ->name('expenses.store');
        });

      // ================= ROLE PERMISSIONS =================
Route::middleware(['role:admin'])->group(function () {
    Route::get('/roles-permissions', [RolePermissionController::class, 'rolePermissions'])
        ->name('role.permissions');

    Route::post('/roles-permissions/update', [RolePermissionController::class, 'updateRolePermission'])
        ->name('role.permissions.update');

    Route::get('/users-permissions', [RolePermissionController::class, 'userPermissions'])
        ->name('user.permissions');

           // 🔴 NEW: SINGLE USER PERMISSIONS
    Route::get('/users/{user}/permissions', [RolePermissionController::class, 'showUserPermissions'])
        ->name('user.permissions.show');


    Route::post('/users-permissions/update', [RolePermissionController::class, 'updateUserPermission'])
        ->name('user.permissions.update');
});

        // ================= CHECKUP FEE AJAX =================
        Route::get('/patients/{id}/checkup-fee', [CheckupController::class, 'getCheckupFee'])
            ->middleware('check_user_permission:view consultation')
            ->name('patients.checkup-fee');
    });

// ================= PUBLIC/COMMON ROUTES =================
Route::get('/', [HomeController::class, 'index'])->name('home');
 Route::get('{any}', [HomeController::class, 'root'])->where('any', '.*');