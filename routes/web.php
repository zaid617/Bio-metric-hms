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
use App\Http\Controllers\DepartmentController;
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
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\Payroll\PayrollAdjustmentController;
use App\Http\Controllers\Payroll\PayrollSettingController;

Auth::routes();

// Clear all cache route
Route::get('/clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return 'All caches cleared successfully!';
})->name('clear');

// ── Admin Dashboard ────────────────────────────────────────────────────────────
Route::prefix('admin')
    ->middleware(['auth:web', 'role:admin|super-admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard'])
            ->middleware('check_user_permission:dashboard.view')
            ->name('dashboard');

        Route::get('branch-stats', [AdminController::class, 'branchStatsByDate'])
            ->middleware('check_user_permission:dashboard.view')
            ->name('branch.stats');

        Route::get('appointments', [CheckupController::class, 'index'])
            ->middleware('check_user_permission:appointments.view')
            ->name('appointments.index');

        Route::get('appointments/create', [CheckupController::class, 'create'])
            ->middleware('check_user_permission:appointments.book')
            ->name('appointments.create');

        Route::post('appointments/store', [CheckupController::class, 'store'])
            ->middleware('check_user_permission:appointments.book')
            ->name('appointments.store');
    });

// ── View-Only Admin ────────────────────────────────────────────────────────────
Route::prefix('view-only-admin')
    ->middleware(['auth:web', 'role:view-only-admin'])
    ->name('view-only-admin.')
    ->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard'])
            ->middleware('check_user_permission:dashboard.view')
            ->name('dashboard');

        Route::get('appointments', [CheckupController::class, 'index'])
            ->middleware('check_user_permission:appointments.view')
            ->name('appointments.index');

        Route::get('appointments/create', [CheckupController::class, 'create'])
            ->middleware('check_user_permission:appointments.book')
            ->name('appointments.create');

        Route::post('appointments/store', [CheckupController::class, 'store'])
            ->middleware('check_user_permission:appointments.book')
            ->name('appointments.store');
    });

// ── Manager Dashboard ──────────────────────────────────────────────────────────
Route::prefix('manager')
    ->middleware(['auth:web', 'role:manager'])
    ->name('manager.')
    ->group(function () {
        Route::get('dashboard', [ManagerDashboardController::class, 'index'])
            ->middleware('check_user_permission:dashboard.view')
            ->name('dashboard');

        Route::get('appointments', [CheckupController::class, 'index'])
            ->middleware('check_user_permission:appointments.view')
            ->name('appointments.index');

        Route::get('appointments/create', [CheckupController::class, 'create'])
            ->middleware('check_user_permission:appointments.book')
            ->name('appointments.create');

        Route::post('appointments/store', [CheckupController::class, 'store'])
            ->middleware('check_user_permission:appointments.book')
            ->name('appointments.store');
    });

// ── Doctor Routes (doctor guard) ───────────────────────────────────────────────
Route::prefix('doctor')
    ->middleware(['auth:doctor', 'role:doctor'])
    ->name('doctor.')
    ->group(function () {
        Route::get('dashboard', [DoctorDashboardController::class, 'index'])
            ->middleware('check_user_permission:dashboard.view')
            ->name('dashboard');

        Route::get('enrollments/{status}', [TreatmentSessionController::class, 'showEnrollments'])
            ->middleware('check_user_permission:enrollments.pending')
            ->name('enrollments.index');

        Route::get('consultations/{status}', [TreatmentSessionController::class, 'index'])
            ->middleware('check_user_permission:consultations.checkup')
            ->name('consultations.index');

        Route::get('consultations/{id}/status-view', [TreatmentSessionController::class, 'viewssStatus'])
            ->middleware('check_user_permission:consultations.checkup')
            ->name('consultations.status-view');

        Route::post('consultations/update-status', [TreatmentSessionController::class, 'updateStatus'])
            ->middleware('check_user_permission:consultations.complete')
            ->name('consultations.update-status');

        Route::post('sessions/store', [TreatmentSessionController::class, 'store'])
            ->middleware('check_user_permission:enrollments.complete')
            ->name('sessions.store');

        Route::get('appointments', [CheckupController::class, 'index'])
            ->middleware('check_user_permission:appointments.view')
            ->name('appointments.index');

        Route::get('appointments/create', [CheckupController::class, 'create'])
            ->middleware('check_user_permission:appointments.book')
            ->name('appointments.create');

        Route::post('appointments/store', [CheckupController::class, 'store'])
            ->middleware('check_user_permission:appointments.book')
            ->name('appointments.store');

        Route::get('sessions', [SessionController::class, 'index'])
            ->middleware('check_user_permission:sessions.ongoing')
            ->name('sessions.index');

        Route::get('ongoing-sessions/{status}', [TreatmentSessionController::class, 'OngoingSessionsOnly'])
            ->middleware('check_user_permission:sessions.ongoing')
            ->name('ongoing-sessions');

        Route::get('session-details/{id}', [TreatmentSessionController::class, 'sessionDetails'])
            ->middleware('check_user_permission:sessions.ongoing')
            ->name('session-details');

        Route::post('sessions/mark-completed', [SessionTimeController::class, 'updateSectionCompleted'])
            ->middleware('check_user_permission:sessions.ongoing')
            ->name('sessions.mark-completed');

        Route::get('feedback', [FeedbackController::class, 'index'])
            ->middleware('check_user_permission:feedback.doctor')
            ->name('feedback.index');

        Route::get('feedback/doctor-list', [FeedbackController::class, 'doctorFeedbackList'])
            ->middleware('check_user_permission:feedback.doctor')
            ->name('feedback.doctor-list');

        Route::get('feedback/patient-list', [FeedbackController::class, 'patientFeedbackList'])
            ->middleware('check_user_permission:feedback.patient')
            ->name('feedback.patient-list');
    });

// ── Receptionist ───────────────────────────────────────────────────────────────
Route::prefix('receptionist')
    ->middleware(['auth:web', 'role:receptionist'])
    ->name('receptionist.')
    ->group(function () {
        Route::get('dashboard', [ReceptionistDashboardController::class, 'index'])
            ->middleware('check_user_permission:dashboard.view')
            ->name('dashboard');

        Route::get('appointments', [CheckupController::class, 'index'])
            ->middleware('check_user_permission:appointments.view')
            ->name('appointments.index');

        Route::get('appointments/create', [CheckupController::class, 'create'])
            ->middleware('check_user_permission:appointments.book')
            ->name('appointments.create');

        Route::post('appointments/store', [CheckupController::class, 'store'])
            ->middleware('check_user_permission:appointments.book')
            ->name('appointments.store');

        Route::get('feedback', [FeedbackController::class, 'index'])
            ->middleware('check_user_permission:feedback.patient')
            ->name('feedback.index');
    });

// ── Shared Routes (web guard, multiple roles) ──────────────────────────────────
Route::middleware(['auth:web', 'role:admin|super-admin|manager|receptionist|view-only-admin|accountant|pharmacist|cashier'])
    ->group(function () {

        // ── Patients ───────────────────────────────────────────────────────
        Route::prefix('patients')->group(function () {
            Route::get('/', [PatientController::class, 'index'])
                ->middleware('check_user_permission:patients.view')
                ->name('patients.index');

            Route::get('/create', [PatientController::class, 'create'])
                ->middleware('check_user_permission:patients.create')
                ->name('patients.create');

            Route::post('/', [PatientController::class, 'store'])
                ->middleware('check_user_permission:patients.create')
                ->name('patients.store');

            Route::get('/search-referrers', [PatientController::class, 'searchReferrers'])
                ->middleware('check_user_permission:patients.view')
                ->name('patients.search-referrers');

            Route::get('/{id}/edit', [PatientController::class, 'edit'])
                ->middleware('check_user_permission:patients.edit')
                ->name('patients.edit');

            Route::put('/{id}', [PatientController::class, 'update'])
                ->middleware('check_user_permission:patients.edit')
                ->name('patients.update');

            Route::get('/{id}', [PatientController::class, 'show'])
                ->middleware('check_user_permission:patients.view')
                ->name('patients.card');

            Route::delete('/{id}', [PatientController::class, 'destroy'])
                ->middleware('check_user_permission:patients.delete')
                ->name('patients.destroy');
        });

        // ── Doctors ────────────────────────────────────────────────────────
        Route::prefix('doctors')->group(function () {
            Route::get('/', [DoctorController::class, 'index'])
                ->middleware('check_user_permission:doctors.view')
                ->name('doctors.index');

            Route::get('/create', [DoctorController::class, 'create'])
                ->middleware('check_user_permission:doctors.create')
                ->name('doctors.create');

            Route::post('/store', [DoctorController::class, 'store'])
                ->middleware('check_user_permission:doctors.create')
                ->name('doctors.store');

            Route::get('/{id}/edit', [DoctorController::class, 'edit'])
                ->middleware('check_user_permission:doctors.edit')
                ->name('doctors.edit');

            Route::put('/{id}', [DoctorController::class, 'update'])
                ->middleware('check_user_permission:doctors.edit')
                ->name('doctors.update');

            Route::get('/{id}', [DoctorController::class, 'show'])
                ->middleware('check_user_permission:doctors.view')
                ->name('doctors.show');

            Route::delete('/{id}', [DoctorController::class, 'destroy'])
                ->middleware('check_user_permission:doctors.delete')
                ->name('doctors.destroy');

            Route::get('/{doctor}/availability', [DoctorAvailabilityController::class, 'index'])
                ->middleware('check_user_permission:doctors.edit')
                ->name('doctors.availability.index');

            Route::post('/{doctor}/availability/store', [DoctorAvailabilityController::class, 'store'])
                ->middleware('check_user_permission:doctors.edit')
                ->name('doctors.availability.store');

            Route::post('/{doctor}/availability/generate-next-month', [DoctorAvailabilityController::class, 'generateNextMonth'])
                ->middleware('check_user_permission:doctors.edit')
                ->name('doctors.availability.generateNextMonth');

            Route::delete('/{doctor}/availability/delete-month', [DoctorAvailabilityController::class, 'deleteMonth'])
                ->middleware('check_user_permission:doctors.delete')
                ->name('doctors.availability.deleteMonth');
        });

        // ── Consultations ──────────────────────────────────────────────────
        Route::prefix('consultations')->group(function () {
            Route::get('/', [CheckupController::class, 'index'])
                ->middleware('check_user_permission:consultations.checkup')
                ->name('consultations.index');

            Route::get('/create', [CheckupController::class, 'create'])
                ->middleware('check_user_permission:consultations.checkup')
                ->name('consultations.create');

            Route::post('/', [CheckupController::class, 'store'])
                ->middleware('check_user_permission:consultations.checkup')
                ->name('consultations.store');

            Route::get('/search-referrers', [CheckupController::class, 'searchReferrers'])
                ->middleware('check_user_permission:consultations.checkup')
                ->name('consultations.search-referrers');

            Route::get('/{id}/edit', [CheckupController::class, 'edit'])
                ->middleware('check_user_permission:appointments.edit')
                ->name('consultations.edit');

            Route::put('/{id}', [CheckupController::class, 'update'])
                ->middleware('check_user_permission:appointments.edit')
                ->name('consultations.update');

            Route::patch('/{id}/paid-amount', [CheckupController::class, 'updatePaidAmount'])
                ->middleware('check_user_permission:appointments.edit')
                ->name('consultations.update-paid-amount');

            Route::delete('/{id}', [CheckupController::class, 'destroy'])
                ->middleware('check_user_permission:appointments.delete')
                ->name('consultations.destroy');

            Route::get('/{id}', [CheckupController::class, 'show'])
                ->middleware('check_user_permission:consultations.checkup')
                ->name('consultations.show');

            Route::get('/{id}/print', [CheckupController::class, 'printSlip'])
                ->middleware('check_user_permission:appointments.print')
                ->name('consultations.print');

            Route::get('/print-custom/{id}', [CheckupController::class, 'printSlipCustom'])
                ->middleware('check_user_permission:appointments.print')
                ->name('consultations.print.custom');

            Route::get('/history/{patient_id}', [CheckupController::class, 'history'])
                ->middleware('check_user_permission:appointments.history')
                ->name('consultations.history');
        });

        // ── Treatment Sessions ─────────────────────────────────────────────
        Route::prefix('treatment-sessions')->group(function () {
            Route::get('/', [TreatmentSessionController::class, 'index'])
                ->middleware('check_user_permission:enrollments.pending')
                ->name('treatment-sessions.index');

            Route::get('/create', [TreatmentSessionController::class, 'create'])
                ->middleware('check_user_permission:enrollments.complete')
                ->name('treatment-sessions.create');

            Route::get('/create/{checkup}', [TreatmentSessionController::class, 'createWithCheckup'])
                ->middleware('check_user_permission:enrollments.complete')
                ->name('treatment-sessions.createWithCheckup');

            Route::post('/', [TreatmentSessionController::class, 'store'])
                ->middleware('check_user_permission:enrollments.complete')
                ->name('treatment-sessions.store');

            Route::get('/{id}/edit', [TreatmentSessionController::class, 'edit'])
                ->middleware('check_user_permission:enrollments.complete')
                ->name('treatment-sessions.edit');

            Route::put('/{id}', [TreatmentSessionController::class, 'update'])
                ->middleware('check_user_permission:enrollments.complete')
                ->name('treatment-sessions.update');

            Route::delete('/{id}', [TreatmentSessionController::class, 'destroy'])
                ->middleware('check_user_permission:enrollments.complete')
                ->name('treatment-sessions.destroy');

            Route::get('/{id}', [TreatmentSessionController::class, 'show'])
                ->middleware('check_user_permission:enrollments.pending')
                ->name('treatment-sessions.show');

            Route::get('/summary', [TreatmentSessionController::class, 'sessionSummary'])
                ->middleware('check_user_permission:enrollments.pending')
                ->name('treatment-sessions.summary');

            Route::get('/sessions/{session_id}', [TreatmentSessionController::class, 'showOngoingSessions'])
                ->middleware('check_user_permission:sessions.ongoing')
                ->name('treatment-sessions.sessions');

            Route::put('/{id}/enrollment-update', [TreatmentSessionController::class, 'enrollmentUpdate'])
                ->middleware('check_user_permission:enrollments.complete')
                ->name('treatment-sessions.enrollmentUpdate');

            Route::get('/{session_id}/add-entry', [TreatmentSessionController::class, 'addEntryForm'])
                ->middleware('check_user_permission:sessions.ongoing')
                ->name('treatment-sessions.add-entry');

            Route::post('/{session_id}/store-entry', [TreatmentSessionController::class, 'storeEntry'])
                ->middleware('check_user_permission:sessions.ongoing')
                ->name('treatment-sessions.store-entry');
        });

        // ── Sessions ───────────────────────────────────────────────────────
        Route::prefix('sessions')->group(function () {
            Route::get('/', [SessionController::class, 'index'])
                ->middleware('check_user_permission:sessions.ongoing')
                ->name('sessions.index');

            Route::post('/{id}/complete', [SessionTimeController::class, 'markCompleted'])
                ->middleware('check_user_permission:sessions.ongoing')
                ->name('sessions.complete');

            Route::delete('/{id}', [SessionTimeController::class, 'destroy'])
                ->middleware('check_user_permission:sessions.completed')
                ->name('sessions.destroy');

            Route::post('/mark-completed', [SessionTimeController::class, 'updateSectionCompleted'])
                ->middleware('check_user_permission:sessions.ongoing')
                ->name('sessions.mark-completed');
        });

        // ── Enrollments ────────────────────────────────────────────────────
        Route::prefix('enrollments')->group(function () {
            Route::get('/{status}', [TreatmentSessionController::class, 'showEnrollments'])
                ->middleware('check_user_permission:enrollments.pending')
                ->name('enrollments');
        });

        // ── Payments ───────────────────────────────────────────────────────
        Route::prefix('payments')->group(function () {
            Route::get('/outstanding-invoices', [PaymentOutstandingController::class, 'index'])
                ->middleware('check_user_permission:payments.outstanding-invoices')
                ->name('accounts.payments');

            Route::get('/completed-invoices', [PaymentOutstandingController::class, 'completedInvoices'])
                ->middleware('check_user_permission:payments.completed-invoices')
                ->name('accounts.completed-invoices');

            Route::get('/appointment-invoices', [PaymentOutstandingController::class, 'appointmentInvoices'])
                ->middleware('check_user_permission:payments.appointment-invoices')
                ->name('payments.appointment-invoices');

            Route::get('/receivable', [PaymentOutstandingController::class, 'receivable'])
                ->middleware('check_user_permission:payments.receivable')
                ->name('payments.receivable');

            Route::get('/outstandings', [PaymentOutstandingController::class, 'index'])
                ->middleware('check_user_permission:payments.outstanding-invoices')
                ->name('payments.outstandings');

            Route::get('/return-payments', [PaymentOutstandingController::class, 'returnPayments'])
                ->middleware('check_user_permission:payments.return')
                ->name('payments.return-payments');

            Route::post('/return', [PaymentOutstandingController::class, 'returnPayment'])
                ->middleware('check_user_permission:payments.return')
                ->name('payments.returnPayment');

            Route::get('/search-patient', [PaymentOutstandingController::class, 'searchPatient'])
                ->middleware('check_user_permission:payments.outstanding-invoices')
                ->name('payments.search-patient');

            Route::get('/fetch-patient-payments', [PaymentOutstandingController::class, 'fetchPatientPayments'])
                ->middleware('check_user_permission:payments.outstanding-invoices')
                ->name('payments.fetch-patient-payments');
        });

        // ── Invoice Ledger ─────────────────────────────────────────────────
        Route::prefix('invoice')->group(function () {
            Route::get('/ledger/{session_id}', [PaymentOutstandingController::class, 'invoiceLedger'])
                ->middleware('check_user_permission:appointments.invoice')
                ->name('invoice.ledger');

            Route::get('/patient-invoice-ledger/{session_id}', [PaymentOutstandingController::class, 'invoiceLedgerr'])
                ->middleware('check_user_permission:appointments.invoice')
                ->name('invoice.ledgerr');

            Route::post('/add-payment', [PaymentOutstandingController::class, 'addPayment'])
                ->middleware('check_user_permission:payments.outstanding-invoices')
                ->name('invoice.add-payment');
        });

        // ── Checkup Payments ───────────────────────────────────────────────
        Route::prefix('checkups')->group(function () {
            Route::get('/invoice/{checkup_id}', [PaymentOutstandingController::class, 'invoiceLedgerCheckup'])
                ->middleware('check_user_permission:appointments.invoice')
                ->name('checkups.invoice');

            Route::post('/refund', [PaymentOutstandingController::class, 'returnCheckupPayment'])
                ->middleware('check_user_permission:payments.return')
                ->name('checkups.refund');
        });

        // ── Payment Transfer ───────────────────────────────────────────────
        Route::prefix('transfer')->group(function () {
            Route::get('/', [PaymentTransactionController::class, 'index'])
                ->middleware('check_user_permission:payments.receivable')
                ->name('transfer.index');

            Route::post('/', [PaymentTransactionController::class, 'store'])
                ->middleware('check_user_permission:payments.receivable')
                ->name('transfer.store');

            Route::get('/get-bank-balance/{id}', [PaymentTransactionController::class, 'getBankBalance'])
                ->middleware('check_user_permission:payments.outstanding-invoices')
                ->name('transfer.getBankBalance');

            Route::get('/get-branch-balance/{id}', [PaymentTransactionController::class, 'getBranchBalance'])
                ->middleware('check_user_permission:payments.outstanding-invoices')
                ->name('transfer.getBranchBalance');
        });

        // ── Ledgers ────────────────────────────────────────────────────────
        Route::prefix('ledger')->group(function () {
            Route::get('/', [LedgerController::class, 'index'])
                ->middleware('check_user_permission:reports.branch-ledgers')
                ->name('ledger.index');

            Route::get('/filter', [LedgerController::class, 'filter'])
                ->middleware('check_user_permission:reports.branch-ledgers')
                ->name('ledger.filter');
        });

        Route::prefix('bank-ledger')->group(function () {
            Route::get('/', [BankLedgerController::class, 'index'])
                ->middleware('check_user_permission:reports.bank-ledgers')
                ->name('bankledger.index');

            Route::get('/filter', [BankLedgerController::class, 'filter'])
                ->middleware('check_user_permission:reports.bank-ledgers')
                ->name('bankledger.filter');
        });

        Route::prefix('income-report')->group(function () {
            Route::get('/', [IncomeReportController::class, 'index'])
                ->middleware('check_user_permission:reports.all-transactions')
                ->name('income.report');
        });

        // ── Feedback ───────────────────────────────────────────────────────
        Route::prefix('feedback')->group(function () {
            Route::get('/doctor-list', [FeedbackController::class, 'doctorFeedbackList'])
                ->middleware('check_user_permission:feedback.doctor')
                ->name('feedback.doctor-list');

            Route::get('/patient-list', [FeedbackController::class, 'patientFeedbackList'])
                ->middleware('check_user_permission:feedback.patient')
                ->name('feedback.patient-list');

            Route::get('/doctor/{sessionId}', [FeedbackController::class, 'doctorFeedbackForm'])
                ->middleware('check_user_permission:feedback.doctor')
                ->name('feedback.doctor');

            Route::post('/doctor-submit', [FeedbackController::class, 'doctorFeedbackSubmit'])
                ->middleware('check_user_permission:feedback.doctor')
                ->name('feedback.doctor-submit');

            Route::get('/patient/{session_id}', [FeedbackController::class, 'patientFeedbackForm'])
                ->middleware('check_user_permission:feedback.patient')
                ->name('feedback.patient');

            Route::post('/patient-submit', [FeedbackController::class, 'patientFeedbackSubmit'])
                ->middleware('check_user_permission:feedback.patient')
                ->name('feedback.patient-submit');
        });

        // ── Dr Consultations (admin/manager side) ──────────────────────────
        Route::prefix('doctor-consultations')->group(function () {
            Route::get('/{status}', [TreatmentSessionController::class, 'index'])
                ->middleware('check_user_permission:consultations.checkup')
                ->name('doctor-consultations.index');

            Route::get('/{id}/status-view', [TreatmentSessionController::class, 'viewssStatus'])
                ->middleware('check_user_permission:consultations.checkup')
                ->name('doctor-consultations.status-view');

            Route::post('/update-status', [TreatmentSessionController::class, 'updateStatus'])
                ->middleware('check_user_permission:consultations.complete')
                ->name('doctor-consultations.update-status');
        });

        // ── Ongoing Sessions ───────────────────────────────────────────────
        Route::prefix('ongoing-sessions')->group(function () {
            Route::get('/{status}', [TreatmentSessionController::class, 'OngoingSessionsOnly'])
                ->middleware('check_user_permission:sessions.ongoing')
                ->name('ongoing-sessions');

            Route::get('/session-details/{id}', [TreatmentSessionController::class, 'sessionDetails'])
                ->middleware('check_user_permission:sessions.ongoing')
                ->name('session-details');
        });

        // ── Employees ──────────────────────────────────────────────────────
        Route::prefix('employees')->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])
                ->middleware('check_user_permission:employees.view')
                ->name('employees.index');

            Route::get('/create', [EmployeeController::class, 'create'])
                ->middleware('check_user_permission:employees.create')
                ->name('employees.create');

            Route::post('/', [EmployeeController::class, 'store'])
                ->middleware('check_user_permission:employees.create')
                ->name('employees.store');

            Route::get('/{id}/edit', [EmployeeController::class, 'edit'])
                ->middleware('check_user_permission:employees.edit')
                ->name('employees.edit');

            Route::put('/{id}', [EmployeeController::class, 'update'])
                ->middleware('check_user_permission:employees.edit')
                ->name('employees.update');

            Route::delete('/{id}', [EmployeeController::class, 'destroy'])
                ->middleware('check_user_permission:employees.delete')
                ->name('employees.destroy');
        });

        // ── Salaries ───────────────────────────────────────────────────────
        Route::prefix('salaries')->group(function () {
            Route::get('/', [EmployeeSalaryController::class, 'index'])
                ->middleware('check_user_permission:attendance.payroll.view')
                ->name('salaries.index');

            Route::get('/create', [EmployeeSalaryController::class, 'create'])
                ->middleware('check_user_permission:attendance.payroll.generate')
                ->name('salaries.create');

            Route::post('/', [EmployeeSalaryController::class, 'store'])
                ->middleware('check_user_permission:attendance.payroll.generate')
                ->name('salaries.store');

            Route::post('/{id}/pay', [EmployeeSalaryController::class, 'markAsPaid'])
                ->middleware('check_user_permission:attendance.payroll.generate')
                ->name('salaries.pay');

            Route::post('/mark-paid', [EmployeeSalaryController::class, 'markPaidWithAdjustment'])
                ->middleware('check_user_permission:attendance.payroll.generate')
                ->name('salaries.markPaid');
        });

        // ── Installments ───────────────────────────────────────────────────
        Route::prefix('installments')->group(function () {
            Route::get('/create/{session_id}', [SessionInstallmentController::class, 'create'])
                ->middleware('check_user_permission:payments.outstanding-invoices')
                ->name('installments.create');

            Route::post('/store', [SessionInstallmentController::class, 'store'])
                ->middleware('check_user_permission:payments.outstanding-invoices')
                ->name('installments.store');
        });

        // ── Settings ───────────────────────────────────────────────────────
        Route::prefix('settings')->group(function () {
            Route::get('/general', [GeneralSettingController::class, 'index'])
                ->middleware('check_user_permission:settings.branch-fee')
                ->name('settings.index');

            Route::post('/general', [GeneralSettingController::class, 'update'])
                ->middleware('check_user_permission:settings.branch-fee')
                ->name('settings.update');

            Route::get('/payroll', [PayrollSettingController::class, 'index'])
                ->middleware('check_user_permission:settings.payroll')
                ->name('payroll.settings.index');

            Route::put('/payroll', [PayrollSettingController::class, 'update'])
                ->middleware('check_user_permission:settings.payroll')
                ->name('payroll.settings.update');
        });

        Route::prefix('payroll')->name('payroll.')->group(function () {
            Route::get('/{payroll}/payslip/preview', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'previewPayslip'])
                ->middleware('check_user_permission:attendance.payroll.view')
                ->name('payslip.preview');

            Route::get('/{payroll}/payslip/download', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'downloadPayslip'])
                ->middleware('check_user_permission:attendance.payroll.view')
                ->name('payslip.download');

            Route::post('/payslips/bulk-download', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'bulkDownloadPayslips'])
                ->middleware('check_user_permission:attendance.payroll.view')
                ->name('payslip.bulk-download');
        });

        Route::prefix('general-settings')->group(function () {
            Route::get('/', [GeneralSettingController::class, 'index'])
                ->middleware('check_user_permission:settings.branch-fee')
                ->name('general-settings.index');

            Route::get('/{id}/edit', [GeneralSettingController::class, 'edit'])
                ->middleware('check_user_permission:settings.branch-fee')
                ->name('general-settings.edit');

            Route::put('/{id}/update', [GeneralSettingController::class, 'update'])
                ->middleware('check_user_permission:settings.branch-fee')
                ->name('general-settings.update');
        });

        // ── Branches ───────────────────────────────────────────────────────
        Route::prefix('branches')->group(function () {
            Route::get('/', [BranchController::class, 'index'])
                ->middleware('check_user_permission:settings.branches.create')
                ->name('branches.index');

            Route::get('/create', [BranchController::class, 'create'])
                ->middleware('check_user_permission:settings.branches.create')
                ->name('branches.create');

            Route::post('/store', [BranchController::class, 'store'])
                ->middleware('check_user_permission:settings.branches.create')
                ->name('branches.store');

            Route::get('/edit/{id}', [BranchController::class, 'edit'])
                ->middleware('check_user_permission:settings.branches.edit')
                ->name('branches.edit');

            Route::put('/update/{id}', [BranchController::class, 'update'])
                ->middleware('check_user_permission:settings.branches.edit')
                ->name('branches.update');

            Route::delete('/delete/{id}', [BranchController::class, 'destroy'])
                ->middleware('check_user_permission:settings.branches.delete')
                ->name('branches.destroy');
        });

        // ── Departments ───────────────────────────────────────────────────
        Route::prefix('departments')->group(function () {
            Route::get('/', [DepartmentController::class, 'index'])
                ->middleware('check_user_permission:settings.departments.create')
                ->name('departments.index');

            Route::get('/create', [DepartmentController::class, 'create'])
                ->middleware('check_user_permission:settings.departments.create')
                ->name('departments.create');

            Route::post('/store', [DepartmentController::class, 'store'])
                ->middleware('check_user_permission:settings.departments.create')
                ->name('departments.store');

            Route::get('/edit/{id}', [DepartmentController::class, 'edit'])
                ->middleware('check_user_permission:settings.departments.edit')
                ->name('departments.edit');

            Route::put('/update/{id}', [DepartmentController::class, 'update'])
                ->middleware('check_user_permission:settings.departments.edit')
                ->name('departments.update');

            Route::delete('/delete/{id}', [DepartmentController::class, 'destroy'])
                ->middleware('check_user_permission:settings.departments.delete')
                ->name('departments.destroy');
        });

        // ── Banks ──────────────────────────────────────────────────────────
        Route::prefix('banks')->group(function () {
            Route::get('/', [BankController::class, 'index'])
                ->middleware('check_user_permission:settings.bank.create')
                ->name('banks.index');

            Route::get('/create', [BankController::class, 'create'])
                ->middleware('check_user_permission:settings.bank.create')
                ->name('banks.create');

            Route::post('/', [BankController::class, 'store'])
                ->middleware('check_user_permission:settings.bank.create')
                ->name('banks.store');

            Route::get('/{id}', [BankController::class, 'show'])
                ->middleware('check_user_permission:settings.bank.create')
                ->name('banks.show');

            Route::get('/{id}/edit', [BankController::class, 'edit'])
                ->middleware('check_user_permission:settings.bank.edit')
                ->name('banks.edit');

            Route::put('/{id}', [BankController::class, 'update'])
                ->middleware('check_user_permission:settings.bank.edit')
                ->name('banks.update');

            Route::delete('/{id}', [BankController::class, 'destroy'])
                ->middleware('check_user_permission:settings.bank.delete')
                ->name('banks.destroy');
        });

        // ── Users ──────────────────────────────────────────────────────────
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])
                ->middleware('check_user_permission:users.view')
                ->name('users.index');

            Route::get('/create', [UserController::class, 'create'])
                ->middleware('check_user_permission:users.create')
                ->name('users.create');

            Route::post('/store', [UserController::class, 'store'])
                ->middleware('check_user_permission:users.create')
                ->name('users.store');

            Route::get('/edit/{id}', [UserController::class, 'edit'])
                ->middleware('check_user_permission:users.edit')
                ->name('users.edit');

            Route::put('/update/{id}', [UserController::class, 'update'])
                ->middleware('check_user_permission:users.edit')
                ->name('users.update');

            Route::delete('/delete/{id}', [UserController::class, 'destroy'])
                ->middleware('check_user_permission:users.delete')
                ->name('users.destroy');

            Route::get('/{user}/permissions', [UserController::class, 'permissions'])
                ->middleware('check_user_permission:users.roles.edit')
                ->name('users.permissions');

            Route::post('/permissions/update', [UserController::class, 'updatePermissions'])
                ->middleware('check_user_permission:users.roles.edit')
                ->name('user.permissions.update');
        });

        // ── Expense Types ──────────────────────────────────────────────────
        Route::prefix('expense-types')->group(function () {
            Route::get('/', [ExpenseTypeController::class, 'index'])
                ->middleware('check_user_permission:expenses.type.create')
                ->name('expense.types');

            Route::post('/store', [ExpenseTypeController::class, 'store'])
                ->middleware('check_user_permission:expenses.type.create')
                ->name('expense.types.store');
        });

        // ── Expenses ───────────────────────────────────────────────────────
        Route::prefix('expenses')->group(function () {
            Route::get('/', [ExpenseController::class, 'index'])
                ->middleware('check_user_permission:expenses.view')
                ->name('expenses.index');

            Route::get('/create', [ExpenseController::class, 'create'])
                ->middleware('check_user_permission:expenses.create')
                ->name('expenses.create');

            Route::post('/store', [ExpenseController::class, 'store'])
                ->middleware('check_user_permission:expenses.create')
                ->name('expenses.store');
        });

        // ── Inventory ──────────────────────────────────────────────────────
        Route::prefix('inventory')->group(function () {
            Route::get('/', [InventoryController::class, 'index'])
                ->middleware('check_user_permission:inventory.view')
                ->name('inventory.index');

            Route::get('/create', [InventoryController::class, 'create'])
                ->middleware('check_user_permission:inventory.create')
                ->name('inventory.create');

            Route::post('/store', [InventoryController::class, 'store'])
                ->middleware('check_user_permission:inventory.create')
                ->name('inventory.store');

            Route::get('/edit/{id}', [InventoryController::class, 'edit'])
                ->middleware('check_user_permission:inventory.edit')
                ->name('inventory.edit');

            Route::put('/update/{id}', [InventoryController::class, 'update'])
                ->middleware('check_user_permission:inventory.edit')
                ->name('inventory.update');

            Route::delete('/delete/{id}', [InventoryController::class, 'destroy'])
                ->middleware('check_user_permission:inventory.delete')
                ->name('inventory.destroy');
        });

        // ── Role Permissions (admin + super-admin) ────────────────────────
        // Accessible to any admin-level role; super-admin gets extra privileges in the UI
        Route::middleware(['role:admin|super-admin'])->group(function () {
            Route::get('/roles-permissions', [RolePermissionController::class, 'rolePermissions'])
                ->name('role.permissions');

            Route::post('/roles-permissions/update', [RolePermissionController::class, 'updateRolePermission'])
                ->name('role.permissions.update');

            Route::get('/users-permissions', [RolePermissionController::class, 'userPermissions'])
                ->name('user.permissions');

            Route::get('/users/{user}/permissions', [RolePermissionController::class, 'showUserPermissions'])
                ->name('user.permissions.show');

            Route::post('/users-permissions/update', [RolePermissionController::class, 'updateUserPermission'])
                ->name('users.permissions.update');
        });

        // ── Attendance Management ──────────────────────────────────────────
        Route::prefix('attendance')->name('attendance.')->group(function () {
            // Devices
            Route::get('/devices', [\App\Http\Controllers\Attendance\AttendanceDeviceController::class, 'index'])
                ->middleware('check_user_permission:attendance.device.create')
                ->name('devices.index');

            Route::get('/devices/create', [\App\Http\Controllers\Attendance\AttendanceDeviceController::class, 'create'])
                ->middleware('check_user_permission:attendance.device.create')
                ->name('devices.create');

            Route::post('/devices', [\App\Http\Controllers\Attendance\AttendanceDeviceController::class, 'store'])
                ->middleware('check_user_permission:attendance.device.create')
                ->name('devices.store');

            Route::post('/devices/sync-all-now', [\App\Http\Controllers\Attendance\AttendanceDeviceController::class, 'syncAllNow'])
                ->middleware('check_user_permission:attendance.device.create')
                ->name('devices.sync-all-now');

            Route::get('/devices/{device}/edit', [\App\Http\Controllers\Attendance\AttendanceDeviceController::class, 'edit'])
                ->middleware('check_user_permission:attendance.device.edit')
                ->name('devices.edit');

            Route::put('/devices/{device}', [\App\Http\Controllers\Attendance\AttendanceDeviceController::class, 'update'])
                ->middleware('check_user_permission:attendance.device.edit')
                ->name('devices.update');

            Route::delete('/devices/{device}', [\App\Http\Controllers\Attendance\AttendanceDeviceController::class, 'destroy'])
                ->middleware('check_user_permission:attendance.device.delete')
                ->name('devices.destroy');

            Route::post('/devices/{device}/test-connection', [\App\Http\Controllers\Attendance\AttendanceDeviceController::class, 'testConnection'])
                ->middleware('check_user_permission:attendance.device.create')
                ->name('devices.test-connection');

            Route::post('/devices/{device}/sync-now', [\App\Http\Controllers\Attendance\AttendanceDeviceController::class, 'syncNow'])
                ->middleware('check_user_permission:attendance.device.create')
                ->name('devices.sync-now');

            Route::get('/devices/{device}/sync-logs', [\App\Http\Controllers\Attendance\AttendanceDeviceController::class, 'syncLogs'])
                ->middleware('check_user_permission:attendance.device.create')
                ->name('devices.sync-logs');

            Route::post('/devices/{device}/toggle-active', [\App\Http\Controllers\Attendance\AttendanceDeviceController::class, 'toggleActive'])
                ->middleware('check_user_permission:attendance.device.edit')
                ->name('devices.toggle-active');

            // Records
            Route::get('/records', [\App\Http\Controllers\Attendance\AttendanceRecordController::class, 'index'])
                ->middleware('check_user_permission:attendance.records.view')
                ->name('records.index');

            Route::get('/records/{employee}/{date}', [\App\Http\Controllers\Attendance\AttendanceRecordController::class, 'show'])
                ->middleware('check_user_permission:attendance.records.view')
                ->name('records.show');

            Route::get('/records/{record}/edit', [\App\Http\Controllers\Attendance\AttendanceRecordController::class, 'edit'])
                ->middleware('check_user_permission:attendance.records.edit')
                ->name('records.edit');

            Route::put('/records/{record}', [\App\Http\Controllers\Attendance\AttendanceRecordController::class, 'update'])
                ->middleware('check_user_permission:attendance.records.edit')
                ->name('records.update');

            Route::post('/records/{record}/mark-missing-checkout', [\App\Http\Controllers\Attendance\AttendanceRecordController::class, 'markMissingCheckout'])
                ->middleware('check_user_permission:attendance.records.edit')
                ->name('records.mark-missing-checkout');

            Route::post('/records/{record}/apply-manual-checkout', [\App\Http\Controllers\Attendance\AttendanceRecordController::class, 'applyManualCheckout'])
                ->middleware('check_user_permission:attendance.records.edit')
                ->name('records.apply-manual-checkout');

            // Payroll
            Route::get('/payroll', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'index'])
                ->middleware('check_user_permission:attendance.payroll.view')
                ->name('payroll.index');

            Route::get('/payroll/generate', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'create'])
                ->middleware('check_user_permission:attendance.payroll.generate')
                ->name('payroll.generate');

            Route::post('/payroll', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'store'])
                ->middleware('check_user_permission:attendance.payroll.generate')
                ->name('payroll.store');

            Route::get('/payroll/{payroll}', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'show'])
                ->middleware('check_user_permission:attendance.payroll.view')
                ->name('payroll.show');

            Route::get('/payroll-employee-view', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'employeeView'])
                ->middleware('check_user_permission:attendance.payroll.view')
                ->name('payroll.employee-view');

            Route::get('/payroll/{payroll}/edit', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'edit'])
                ->middleware('check_user_permission:attendance.payroll.adjustments')
                ->name('payroll.edit');

            Route::put('/payroll/{payroll}', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'update'])
                ->middleware('check_user_permission:attendance.payroll.adjustments')
                ->name('payroll.update');

            Route::post('/payroll/{payroll}/approve', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'approve'])
                ->middleware('check_user_permission:attendance.payroll.generate')
                ->name('payroll.approve');

            Route::post('/payroll/{payroll}/mark-paid', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'markPaid'])
                ->middleware('check_user_permission:attendance.payroll.generate')
                ->name('payroll.mark-paid');

            Route::post('/payroll/{payroll}/regenerate', [\App\Http\Controllers\Attendance\AttendancePayrollController::class, 'regenerate'])
                ->middleware('check_user_permission:attendance.payroll.generate')
                ->name('payroll.regenerate');

            // Payroll Adjustments
            Route::get('/payroll-adjustments', [PayrollAdjustmentController::class, 'index'])
                ->middleware('check_user_permission:attendance.payroll.adjustments')
                ->name('payroll.adjustments.index');

            Route::get('/payroll-adjustments/create', [PayrollAdjustmentController::class, 'create'])
                ->middleware('check_user_permission:attendance.payroll.adjustments')
                ->name('payroll.adjustments.create');

            Route::post('/payroll-adjustments', [PayrollAdjustmentController::class, 'store'])
                ->middleware('check_user_permission:attendance.payroll.adjustments')
                ->name('payroll.adjustments.store');

            Route::get('/payroll-adjustments/{adjustment}', [PayrollAdjustmentController::class, 'show'])
                ->middleware('check_user_permission:attendance.payroll.view')
                ->name('payroll.adjustments.show');

            Route::get('/payroll-adjustments/{adjustment}/edit', [PayrollAdjustmentController::class, 'edit'])
                ->middleware('check_user_permission:attendance.payroll.adjustments')
                ->name('payroll.adjustments.edit');

            Route::put('/payroll-adjustments/{adjustment}', [PayrollAdjustmentController::class, 'update'])
                ->middleware('check_user_permission:attendance.payroll.adjustments')
                ->name('payroll.adjustments.update');

            Route::delete('/payroll-adjustments/{adjustment}', [PayrollAdjustmentController::class, 'destroy'])
                ->middleware('check_user_permission:attendance.payroll.adjustments')
                ->name('payroll.adjustments.destroy');

            // Reports
            Route::get('/reports/daily', [\App\Http\Controllers\Attendance\AttendanceReportController::class, 'daily'])
                ->middleware('check_user_permission:reports.all-transactions')
                ->name('reports.daily');

            Route::get('/reports/monthly', [\App\Http\Controllers\Attendance\AttendanceReportController::class, 'monthly'])
                ->middleware('check_user_permission:reports.all-transactions')
                ->name('reports.monthly');

            Route::get('/reports/employee/{employee}', [\App\Http\Controllers\Attendance\AttendanceReportController::class, 'employeeReport'])
                ->middleware('check_user_permission:reports.all-transactions')
                ->name('reports.employee');

            Route::get('/reports/branch', [\App\Http\Controllers\Attendance\AttendanceReportController::class, 'branchReport'])
                ->middleware('check_user_permission:reports.all-transactions')
                ->name('reports.branch');

            Route::get('/reports/late', [\App\Http\Controllers\Attendance\AttendanceReportController::class, 'lateReport'])
                ->middleware('check_user_permission:reports.all-transactions')
                ->name('reports.late');

            Route::get('/reports/overtime', [\App\Http\Controllers\Attendance\AttendanceReportController::class, 'overtimeReport'])
                ->middleware('check_user_permission:reports.all-transactions')
                ->name('reports.overtime');
        });

        // ── Checkup fee AJAX ───────────────────────────────────────────────
        Route::get('/patients/{id}/checkup-fee', [CheckupController::class, 'getCheckupFee'])
            ->middleware('check_user_permission:consultations.checkup')
            ->name('patients.checkup-fee');
    });

// ── Public / Fallback ──────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('{any}', [HomeController::class, 'root'])->where('any', '.*');
