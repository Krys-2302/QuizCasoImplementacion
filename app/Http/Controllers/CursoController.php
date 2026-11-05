<?php

namespace App\Http\Controllers;
use App\Models\Curso;
use Illuminate\Http\Request;

class CursoController extends Controller
{
  public function store(Request $request)
    {
        $data = $request->validate([
            'sigla'        => 'required|string|unique:cursos,sigla',
            'nombre_curso' => 'required|string',
            'creditos'     => 'required|integer|min:1',
        ]);

        $curso = Curso::create($data);

        return response()->json($curso, 201);
    }
}
