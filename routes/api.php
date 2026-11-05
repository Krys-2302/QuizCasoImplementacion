<?php

use App\Http\Controllers\CursoController;
use App\Http\Controllers\GrupoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/cursos', [CursoController::class, 'store']);

