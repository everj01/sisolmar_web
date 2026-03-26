# Instrucciones para index_nuevo_dj.php

Para activar el Formulario DJ en la página principal, sigue estos pasos:

### 1. Agregar el botón "Modal DJ"
Busca una ubicación adecuada en el cuerpo de la página (ej. después de la línea 685, dentro del `container`) y agrega:

```html
<!-- Botón para abrir el Modal DJ -->
<div class="row mb-4">
    <div class="col-md-12 text-right" style="padding: 10px 20px;">
        <button type="button" class="btn btn-primary btn-cons" onclick="abrirModalDJ()">
            <i class="fa fa-file-text"></i> MODAL DJ
        </button>
    </div>
</div>
```

### 2. Incluir el Modal y las dependencias JS/CSS
Al final del archivo `index_nuevo_dj.php`, antes del `include('CIncludes/footer.php')` o antes de cerrar el body, agrega:

```php
<!-- Dependencias para Formulario DJ -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- Estructura del Modal -->
<?php include('CIncludes/modal_dj.php'); ?>

<!-- Lógica del Formulario -->
<script src="js/gestion_dj_migrado.js"></script>
```

### 3. Subir los archivos al servidor
Copia los archivos de la carpeta local `C:\Users\JJULCA\Desktop\solmar\sisolmar_web\MIGRACION_DJ\` a sus respectivas ubicaciones en el servidor `\\192.168.10.5\Extranet_2013\SIP_2.0\`:

- `ajax_gestion_dj.php` -> Raíz del proyecto.
- `ClsDatos.DJ.php` -> Carpeta `CDatos/`.
- `modal_dj.php` -> Carpeta `CIncludes/`.
- `gestion_dj_migrado.js` -> Carpeta `js/`.
