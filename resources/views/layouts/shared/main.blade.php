<!DOCTYPE html>
<html lang="en">
<script>
    // Dark Mode: Inicializar tema antes del render para evitar flash de contenido
    // Por defecto: modo claro. Solo activa dark si el usuario lo guardó previamente
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark');
    }
</script>