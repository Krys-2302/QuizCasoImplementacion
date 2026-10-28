<?php

namespace Tests\Unit;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\EstudianteMatriculado;
use App\Models\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    use RefreshDatabase;

    public function test_grupo_contiene_toda_la_informacion(): void
    {
        $curso = Curso::create([
            'sigla' => 'ISW-101', 'nombre_curso' => 'Análisis y diseño de sistemas', 'creditos' => 4,
        ]);

        $docente1 = Docente::create(['cod_empleado' => 1001, 'nombre_empleado' => 'Ana', 'apellidos_empleado' => 'Mora Rojas']);
        $docente2 = Docente::create(['cod_empleado' => 1002, 'nombre_empleado' => 'Luis', 'apellidos_empleado' => 'Vargas Solís']);

        $grupo = Grupo::create([
            'numero_ciclo' => 2, 'anio' => 2026, 'numero_grupo' => 1,
            'abierto' => true, 'curso_id' => $curso->id,
        ]);
        $grupo->docentes()->attach([$docente1->id, $docente2->id]);

        for ($i = 1; $i <= 15; $i++) {
            $estudiante = Estudiante::create([
                'carne' => 'B'.str_pad($i, 5, '0', STR_PAD_LEFT),
                'nombre' => "Estudiante$i",
                'apellidos' => 'Prueba Uno',
                'docente_id' => $docente1->id,
            ]);
            EstudianteMatriculado::create([
                'grupo_id' => $grupo->id,
                'estudiante_id' => $estudiante->id,
                'nota' => 70 + $i,
            ]);
        }

        $grupo->load(['curso', 'docentes', 'estudiantes.consejero']);

        $this->assertSame(2, $grupo->numero_ciclo);
        $this->assertSame(2026, $grupo->anio);
        $this->assertSame(1, $grupo->numero_grupo);
        $this->assertTrue($grupo->abierto);

        $this->assertSame('ISW-101', $grupo->curso->sigla);

        $this->assertGreaterThanOrEqual(1, $grupo->docentes->count());
        $this->assertLessThanOrEqual(2, $grupo->docentes->count());

        $this->assertGreaterThanOrEqual(15, $grupo->estudiantes->count());
        foreach ($grupo->estudiantes as $est) {
            $this->assertNotNull($est->pivot->nota);
            $this->assertNotNull($est->consejero);
        }
    }
}
