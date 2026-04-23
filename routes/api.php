<?php

use App\Http\Controllers\LeadGenerationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes – Lead Generation (Lusha)
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api (set in bootstrap/app.php).
| Sanctum auth middleware can be added here when multi-user auth is required.
|
*/

Route::prefix('leads')->group(function () {

    // ── Lusha live queries ─────────────────────────────────────────────────
    Route::get('prospect',         [LeadGenerationController::class, 'prospect']);
    Route::post('enrich/person',   [LeadGenerationController::class, 'enrichPerson']);
    Route::post('enrich/company',  [LeadGenerationController::class, 'enrichCompany']);
    Route::post('import',          [LeadGenerationController::class, 'import']);

    // ── Persisted leads CRUD ───────────────────────────────────────────────
    Route::get('/',                [LeadGenerationController::class, 'index']);
    Route::get('{lead}',           [LeadGenerationController::class, 'show']);
    Route::patch('{lead}',         [LeadGenerationController::class, 'update']);
    Route::delete('{lead}',        [LeadGenerationController::class, 'destroy']);
});
