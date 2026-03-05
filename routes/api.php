<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClassAssignmentController;
use App\Http\Controllers\Api\ClassScheduleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FinanceTransactionController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\HabitController;
use App\Http\Controllers\Api\HealthMetricController;
use App\Http\Controllers\Api\MonthlyBudgetController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\PrayerTimeController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WeatherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Solara API Routes
|--------------------------------------------------------------------------
|
| Semua endpoint menggunakan prefix /api dan format JSON.
| Auth menggunakan Laravel Sanctum (Bearer Token).
|
*/

// ==========================================
// 🔓 Public Routes (tanpa auth)
// ==========================================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Telegram Webhook (tanpa auth, diverifikasi sendiri)
Route::post('/webhook/telegram', [\App\Http\Controllers\TelegramWebhookController::class, 'handle']);

// ==========================================
// 🔒 Protected Routes (perlu auth:sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // --- Auth & Profile ---
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });

    // --- Dashboard ---
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // --- Tasks ---
    Route::apiResource('tasks', TaskController::class);

    // --- Habits ---
    Route::get('/habits/stats', [HabitController::class, 'stats']);
    Route::apiResource('habits', HabitController::class);
    Route::post('/habits/{habit}/log', [HabitController::class, 'log']);
    Route::get('/habits/{habit}/logs', [HabitController::class, 'logs']);
    Route::post('/habits/{habit}/restore-streak', [HabitController::class, 'restoreStreak']);

    // --- Notes ---
    Route::apiResource('notes', NoteController::class);

    // --- Goals + Milestones ---
    Route::apiResource('goals', GoalController::class);
    Route::post('/goals/{goal}/milestones', [GoalController::class, 'storeMilestone']);
    Route::put('/goals/{goal}/milestones/{milestone}', [GoalController::class, 'updateMilestone']);
    Route::delete('/goals/{goal}/milestones/{milestone}', [GoalController::class, 'destroyMilestone']);

    // --- Categories ---
    Route::apiResource('categories', CategoryController::class);

    // --- Finance ---
    Route::get('/finance/summary', [FinanceTransactionController::class, 'summary']);
    Route::apiResource('finance/transactions', FinanceTransactionController::class)
        ->parameters(['transactions' => 'financeTransaction']);
    Route::apiResource('finance/budgets', MonthlyBudgetController::class)
        ->parameters(['budgets' => 'monthlyBudget']);

    // --- Class Schedule ---
    Route::get('/schedules/today', [ClassScheduleController::class, 'today']);
    Route::apiResource('schedules', ClassScheduleController::class)
        ->parameters(['schedules' => 'classSchedule']);

    // --- Class Assignments ---
    Route::apiResource('assignments', ClassAssignmentController::class)
        ->parameters(['assignments' => 'classAssignment']);

    // --- Health Metrics ---
    Route::post('/health/sync', [HealthMetricController::class, 'sync']);
    Route::apiResource('health', HealthMetricController::class)
        ->parameters(['health' => 'healthMetric']);

    // --- Weather (proxy API, no DB) ---
    Route::get('/weather', [WeatherController::class, 'current']);

    // --- Prayer Times (proxy API, no DB) ---
    Route::get('/prayer-times', [PrayerTimeController::class, 'index']);

    // --- Settings ---
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'update']);
    Route::put('/settings/telegram', [SettingsController::class, 'updateTelegram']);
    Route::put('/settings/location', [SettingsController::class, 'updateLocation']);

    // --- Portfolio (protected, manage own portfolio) ---
    Route::prefix('portfolio')->group(function () {
        // Profile
        Route::get('/profile', [\App\Http\Controllers\Api\PortfolioProfileController::class, 'show']);
        Route::put('/profile', [\App\Http\Controllers\Api\PortfolioProfileController::class, 'update']);
        Route::post('/profile/avatar', [\App\Http\Controllers\Api\PortfolioProfileController::class, 'uploadAvatar']);

        // Projects
        Route::apiResource('projects', \App\Http\Controllers\Api\PortfolioProjectController::class)
            ->parameters(['projects' => 'portfolioProject']);
        Route::post('/projects/{portfolioProject}/screenshots', [\App\Http\Controllers\Api\PortfolioProjectController::class, 'uploadScreenshots']);

        // Certificates
        Route::apiResource('certificates', \App\Http\Controllers\Api\PortfolioCertificateController::class)
            ->parameters(['certificates' => 'portfolioCertificate']);

        // Experiences
        Route::apiResource('experiences', \App\Http\Controllers\Api\PortfolioExperienceController::class)
            ->parameters(['experiences' => 'portfolioExperience']);
    });
});

// ==========================================
// 🌐 Public Portfolio (no auth, read-only)
// ==========================================
Route::prefix('public/portfolio')->group(function () {
    Route::get('/{userId}', function ($userId) {
        $user = \App\Models\User::findOrFail($userId);
        return response()->json([
            'success' => true,
            'data' => [
                'profile' => $user->portfolioProfile,
                'projects' => $user->portfolioProjects()->where('is_visible', true)->orderBy('sort_order')->get(),
                'certificates' => $user->portfolioCertificates()->where('is_visible', true)->orderBy('sort_order')->get(),
                'experiences' => $user->portfolioExperiences()->where('is_visible', true)->orderBy('sort_order')->orderByDesc('start_date')->get(),
            ],
        ]);
    });
});
