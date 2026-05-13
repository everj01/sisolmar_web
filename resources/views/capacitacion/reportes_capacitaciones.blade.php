@extends('layouts.vertical', ['title' => 'Reportes de Capacitaciones'])
@section('css')
<style>
.card-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.card-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
}
</style>
@endsection
@section('content')
<div x-data="reportesApp()" class="px-6 py-6">
    <div class="relative overflow-hidden rounded-2xl border border-default-200/60 bg-gradient-to-br from-white to-default-50 shadow-sm mb-6">
        <div class="absolute top-0 right-0 w-72 h-72 bg-primary/5 rounded-full blur-3xl"></div>
        <div class="relative p-8">
            <div class="inline-flex items-center gap-2 w-fit px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-medium">
                <i class="ti ti-report text-sm"></i>
                Reportes de capacitaciones
            </div>
            <div class="mt-4">
                <h1 class="text-3xl font-bold tracking-tight text-default-900">Reportes</h1>
                <p class="mt-3 text-sm leading-7 text-default-600 max-w-4xl">
                    Genere reportes detallados sobre el estado de las capacitaciones del personal.
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div class="card-hover relative overflow-hidden rounded-2xl border border-default-200/60 bg-white shadow-sm cursor-pointer group">
            <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative p-6 flex flex-col h-full">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                        <i class="ti ti-file-report text-2xl text-primary"></i>
                    </div>
                    <i class="ti ti-chevron-right text-default-300 group-hover:text-primary transition-colors text-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-default-900 mb-2">Reporte por capacitación</h3>
                <p class="text-sm text-default-500 leading-relaxed flex-grow mb-4">
                    Genera un reporte por capacitación, visualizando rápidamente qué personal está pendiente, aprobado y/o desaprobado.
                </p>
                <button class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-600 transition-colors shadow-sm w-fit">
                    <i class="ti ti-download"></i>
                    Generar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function reportesApp() {
    return {};
}
</script>
@endsection
