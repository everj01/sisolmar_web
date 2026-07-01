<?php

  namespace App\Models;

  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Model;

  class CursoProgramacion extends Model
  {
      use HasFactory;

      protected $connection = 'sqlsrv';
      protected $table = 'sw_cursos_programacion';

      protected $primaryKey = 'codigo';
      public $incrementing = true;
      protected $keyType = 'int';

      protected $fillable = [
          'codigo_programacion',
          'cod_curso',
          'fecha_inicio',
          'fecha_final',
          'periodo',
          'tipo',
          'estado_periodo',
          'creado_por',
          'fecha_creacion',
          'modificado_por',
          'fecha_modificacion',
          'habilitado'
      ];

      public $timestamps = false;

      public function curso()
      {
          return $this->belongsTo(Cursos::class, 'cod_curso', 'codigo');
      }

      public function matriculas()
      {
          return $this->hasMany(Matricula::class, 'cod_programacion', 'codigo_programacion');
      }

      public function scopeVigentes($query)
      {
          return $query->where('estado_periodo', 'VIGENTE');
      }

      public function scopeDelCurso($query, $cursoId)
      {
          return $query->where('cod_curso', $cursoId);
      }

      public function scopeHabilitados($query)
      {
          return $query->where('habilitado', 1);
      }
  }