<?php
// Continue to Editando informações do usuário.
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarberController;

Route::get('/ping', function() {
  return ['pong' => true];
});

// Users routes
Route::post('/user', [AuthController::class, 'create']); // Create new user
Route::post('/auth/login', [AuthController::class, 'login']); // Sign in user
Route::get('/401', [AuthController::class, 'unauthorized'])->name('login'); // Route login redirect

Route::middleware('auth:api')->group(function() {
  Route::post('/auth/logout', [AuthController::class, 'logout']); // Sign up user
  Route::post('/auth/refresh', [AuthController::class, 'refresh']); // Generate new Token

  /* Delete this route
  Route::get('/random', [BarberController::class, 'createRandom']); // Populating database with barbers
  */

  Route::get('/user', [UserController::class, 'read']); // Read info one user
  Route::put('/user', [UserController::class, 'update']); // Update info user logged
  Route::post('/user/avatar', [UserController::class, 'updateAvatar']); // Update avatar from user logged
  Route::get('/user/favorites', [UserController::class, 'getFavorites']); // Get barbers favorite from user
  Route::post('/user/favorite', [UserController::class, 'toggleFavorite']); // Add new favorite barber
  Route::get('/user/appointments', [UserController::class, 'getAppointments']); // Get appointments from user

  // Barber Routes
  Route::get('/barbers', [BarberController::class, 'list']); // List of barbers
  Route::get('/barber/{id}', [BarberController::class, 'one']); // Get one barber by id query string
  Route::post('/barber/{id}/appointment', [BarberController::class, 'setAppointment']); // To do appointment

  Route::get('/search', [BarberController::class, 'search']); // Search barber
});
