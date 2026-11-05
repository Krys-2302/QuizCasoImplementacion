<?php

namespace Tests\Unit;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\EstudianteMatriculado;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoTest extends TestCase
{
    use RefreshDatabase;

    private function cursoIF6100(): Curso
    {
        return Curso::create([
            'sigla' => 'IF6100', 'nombre_curso' => 'Análisis y Diseño de Sistemas', 'creditos' => 4,
        ]);
    }

    private function grupo01(Curso $curso, bool $abierto = false): Grupo
    {
        return Grupo::create([
            'curso_id' => $curso->id, 'numero_ciclo' => 2, 'anio' => 2026,
            'numero_grupo' => 1, 'abierto' => $abierto,
        ]);
    }

    private function docente1001(): Docente
    {
        return Docente::create(['cod_empleado' => 1001, 'nombre_empleado' => 'Ana', 'apellidos_empleado' => 'Mora Vargas']);
    }

    private function docente1002(): Docente
    {
        return Docente::create(['cod_empleado' => 1002, 'nombre_empleado' => 'Luis', 'apellidos_empleado' => 'Rojas Solano']);
    }

    private function docente1003(): Docente
    {
        return Docente::create(['cod_empleado' => 1003, 'nombre_empleado' => 'Carla', 'apellidos_empleado' => 'Jiménez Araya']);
    }

    private function matricularEstudiantes(Grupo $grupo, int $cantidad): void
    {
        $consejero = Docente::create([
            'cod_empleado' => 2000, 'nombre_empleado' => 'Marta', 'apellidos_empleado' => 'Quesada Campos',
        ]);

        for ($i = 1; $i <= $cantidad; $i++) {
            $estudiante = Estudiante::create([
                'carne' => 'B'.str_pad($i, 5, '0', STR_PAD_LEFT),
                'nombre' => "Estudiante$i",
                'apellidos' => 'Prueba Uno',
                'docente_id' => $consejero->id,
            ]);

            $grupo->matricular($estudiante);
        }
    }

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

    public function test_cp01_registra_un_grupo_con_toda_su_informacion_y_su_curso(): void
    {
        $curso = $this->cursoIF6100();
        $creado = $this->grupo01($curso, abierto: true);

        $grupo = Grupo::find($creado->id);

        $this->assertSame(2, $grupo->numero_ciclo);
        $this->assertSame(2026, $grupo->anio);
        $this->assertSame(1, $grupo->numero_grupo);
        $this->assertTrue($grupo->abierto); 

        $this->assertSame('IF6100', $grupo->curso->sigla);
        $this->assertSame(4, $grupo->curso->creditos);

        $this->assertSame(1, Grupo::count());
    }

    public function test_cp02_un_curso_tiene_varios_grupos_y_se_distinguen_los_abiertos(): void
    {
        $curso = $this->cursoIF6100();

        foreach ([1 => true, 2 => true, 3 => false] as $numero => $abierto) {
            Grupo::create([
                'curso_id' => $curso->id, 'numero_ciclo' => 2, 'anio' => 2026,
                'numero_grupo' => $numero, 'abierto' => $abierto,
            ]);
        }

        $curso = $curso->fresh();
        $abiertos = $curso->grupos->filter(fn (Grupo $g) => $g->abierto);

        $this->assertCount(3, $curso->grupos);
        $this->assertCount(2, $abiertos);
        $this->assertSame([1, 2], $abiertos->pluck('numero_grupo')->sort()->values()->all());
        $this->assertSame([$curso->id], $curso->grupos->pluck('curso_id')->unique()->all());
    }

    public function test_cp03_un_grupo_acepta_dos_docentes(): void
    {
        $grupo = $this->grupo01($this->cursoIF6100());
        $d1001 = $this->docente1001();
        $d1002 = $this->docente1002();

        $grupo->asignarDocente($d1001);
        $grupo->asignarDocente($d1002);

        $grupo = $grupo->fresh();

        $this->assertCount(2, $grupo->docentes);
        $this->assertSame([1001, 1002], $grupo->docentes->pluck('cod_empleado')->sort()->values()->all());
        $this->assertContains($grupo->id, $d1001->grupos->pluck('id')->all());

        $this->assertDatabaseCount('docente_grupo', 2);
    }

    public function test_cp04_un_grupo_rechaza_un_tercer_docente(): void
    {
        $grupo = $this->grupo01($this->cursoIF6100());
        $grupo->asignarDocente($this->docente1001());
        $grupo->asignarDocente($this->docente1002());
        $d1003 = $this->docente1003();

        $lanzada = false;
        try {
            $grupo->asignarDocente($d1003);
        } catch (DomainException) {
            $lanzada = true;
        }
        $this->assertTrue($lanzada, 'Se esperaba una DomainException al asignar un tercer docente.');

        $this->assertCount(2, $grupo->fresh()->docentes);
        $this->assertDatabaseMissing('docente_grupo', ['docente_id' => $d1003->id]);
    }

    public function test_cp05_un_grupo_con_14_estudiantes_no_puede_abrirse(): void
    {
        $grupo = $this->grupo01($this->cursoIF6100());
        $grupo->asignarDocente($this->docente1001());
        $this->matricularEstudiantes($grupo, 14);

        $this->assertFalse($grupo->puedeAbrirse());

        $lanzada = false;
        try {
            $grupo->abrir();
        } catch (DomainException) {
            $lanzada = true;
        }
        $this->assertTrue($lanzada, 'abrir() debía lanzar DomainException con 14 estudiantes.');

        $this->assertFalse($grupo->fresh()->abierto);
    }

    public function test_cp06_un_grupo_con_15_estudiantes_y_un_docente_se_abre(): void
    {
        $grupo = $this->grupo01($this->cursoIF6100());
        $grupo->asignarDocente($this->docente1001());
        $this->matricularEstudiantes($grupo, 15);

        $this->assertTrue($grupo->puedeAbrirse());

        $grupo->abrir(); 

        $this->assertTrue($grupo->fresh()->abierto);
    }
}