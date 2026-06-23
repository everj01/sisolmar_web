  @extends('layouts.vertical', ['title' => 'Sin permiso'])

  @section('content')
  <div class="flex flex-col items-center justify-center py-20 text-center">
      <span class="text-6xl font-bold text-default-300">403</span>
      <h2 class="mt-4 text-xl font-semibold text-default-700">Acceso no permitido</h2>
      <p class="mt-2 text-default-500">No tenés permisos para acceder a esta sección.</p>
      <a href="/home" class="mt-6 bg-primary text-white px-5 py-2 rounded-lg hover:bg-primary-800">
          Volver al inicio
      </a>
  </div>
  @endsection