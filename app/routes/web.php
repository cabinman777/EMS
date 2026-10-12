<?php
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;
Route::redirect('/', '/imports');
Route::middleware('auth')->group(function () {
 Route::get('/imports',[ImportController::class,'create'])->name('imports.create');
 Route::post('/imports/analyse',[ImportController::class,'analyse'])->name('imports.analyse');
 Route::post('/imports/confirm',[ImportController::class,'confirm'])->name('imports.confirm');
});
require __DIR__.'/auth.php';

Route::get("/dashboard", fn () => redirect()->route("imports.create"))->middleware("auth")->name("dashboard");

Route::middleware("auth")->group(function () {
 Route::get("/profile", [\App\Http\Controllers\ProfileController::class, "edit"])->name("profile.edit");
 Route::patch("/profile", [\App\Http\Controllers\ProfileController::class, "update"])->name("profile.update");
 Route::delete("/profile", [\App\Http\Controllers\ProfileController::class, "destroy"])->name("profile.destroy");
});
