<?php
use Illuminate\Support\Facades\Route;
Route::get('/', fn () => response()->json(['app' => 'Kindred Vet Clinic API', 'health' => '/api/health']));
