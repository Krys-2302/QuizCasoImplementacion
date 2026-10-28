<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    protected $fillable = ['carne', 'nombre', 'apellidos', 'docente_id'];

    public function consejero(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(EstudianteMatriculado::class);
    }
}
