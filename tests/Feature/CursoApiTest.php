<?php

namespace Tests\Feature;

use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CursoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_un_curso_en_la_base_de_datos_mediante_la_url(): void
    {
        $this->withoutExceptionHandling();

        $datos = [
            'sigla' => 'ISW-101',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ];

        // Se envían los datos a la URL
        $response = $this->postJson('/api/cursos', $datos);

        // La respuesta es correcta
        $response->assertCreated()
            ->assertJsonFragment(['sigla' => 'ISW-101']);

        // El curso quedó guardado en la BD
        $this->assertDatabaseHas('cursos', $datos);
    }

    public function test_rechaza_curso_con_datos_invalidos(): void
    {
        $this->postJson('/api/cursos', ['sigla' => ''])
            ->assertStatus(422);

        $this->assertDatabaseCount('cursos', 0);
    }

    public function test_rechaza_sigla_duplicada(): void
    {
        Curso::create([
            'sigla' => 'ISW-101',
            'nombre_curso' => 'Curso original',
            'creditos' => 4,
        ]);

        $this->postJson('/api/cursos', [
            'sigla' => 'ISW-101',
            'nombre_curso' => 'Otro curso',
            'creditos' => 3,
        ])->assertStatus(422);
    }
}
