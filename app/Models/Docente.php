<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    protected $fillable = ['cod_empleado', 'nombre_empleado', 'apellidos_empleado'];

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class);
    }

    public function estudiantesAconsejados(): HasMany
    {
        return $this->hasMany(Estudiante::class);
    }
}
