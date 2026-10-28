<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    protected $fillable = ['numero_ciclo', 'anio', 'numero_grupo', 'abierto', 'curso_id'];

    protected $casts = ['abierto' => 'boolean'];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class);
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(EstudianteMatriculado::class);
    }

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_matriculados')->withPivot('nota');
    }
}
