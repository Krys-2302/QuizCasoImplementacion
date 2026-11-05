<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    public const MIN_DOCENTES = 1;
    public const MAX_DOCENTES = 2;
    public const MIN_ESTUDIANTES = 15;

    protected $fillable = ['numero_ciclo', 'anio', 'numero_grupo', 'abierto', 'curso_id'];

    protected $casts = [
        'numero_ciclo' => 'integer',
        'anio' => 'integer',
        'numero_grupo' => 'integer',
        'abierto' => 'boolean',
    ];

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

    public function asignarDocente(Docente $docente): void
    {
        if ($this->docentes()->whereKey($docente->getKey())->exists()) {
            throw new DomainException('El docente ya imparte este grupo.');
        }

        if ($this->docentes()->count() >= self::MAX_DOCENTES) {
            throw new DomainException('Un grupo no puede tener más de '.self::MAX_DOCENTES.' docentes.');
        }

        $this->docentes()->attach($docente->getKey());
        $this->unsetRelation('docentes');
    }

    public function matricular(Estudiante $estudiante): void
    {
        // Sin validar abierto/cerrado: CP-05 matricula en un grupo cerrado.
        $this->matriculas()->create(['estudiante_id' => $estudiante->getKey()]);
        $this->unsetRelation('matriculas');
    }

    public function puedeAbrirse(): bool
    {
        $docentes = $this->docentes()->count();

        return $docentes >= self::MIN_DOCENTES
            && $docentes <= self::MAX_DOCENTES
            && $this->matriculas()->count() >= self::MIN_ESTUDIANTES;
    }

    public function abrir(): void
    {
        if (! $this->puedeAbrirse()) {
            throw new DomainException(
                'El grupo requiere entre '.self::MIN_DOCENTES.' y '.self::MAX_DOCENTES
                .' docentes y al menos '.self::MIN_ESTUDIANTES.' estudiantes matriculados para abrirse.'
            );
        }

        $this->abierto = true;
        $this->save();
    }
}