# Configuración para Producción - Sistema de Matrícula

## ⚠️ IMPORTANTE: Queue Worker debe estar SIEMPRE corriendo

El sistema de matrícula usa **jobs en cola** para enviar emails y guardar datos.
Si el Queue Worker NO está corriendo, las matrículas se quedarán en cola sin procesarse.

---

## 🌐 Arquitectura del Sistema

```
┌─────────────────┐         ┌──────────────────────┐         ┌─────────────────┐
│  CLIENTE        │         │  SERVIDOR WEB        │         │  SERVIDOR BD    │
│  (Navegador)    │────────▶│  Laravel App         │────────▶│  SQL Server     │
│                 │  HTTP   │  + Queue Worker      │  ODBC   │  192.168.10.21  │
└─────────────────┘         └──────────────────────┘         └─────────────────┘
                                     ▲
                                     │
                            ✅ AQUÍ se ejecuta
                               el queue worker
```

### 📍 ¿Dónde se ejecuta el Queue Worker?

- ✅ **EN EL SERVIDOR WEB** donde está instalado Laravel (donde corre la aplicación)
- ❌ **NO** en la computadora del cliente que usa el navegador
- ❌ **NO** en el servidor de base de datos

**El cliente solo abre el navegador** → La aplicación procesa en el servidor → El worker procesa los jobs

---

## 🔧 Configuración Automática en Windows Server

### Opción 1: Task Scheduler (RECOMENDADO)

1. **Abrir Task Scheduler (Programador de tareas)**
   - Presiona `Windows + R`
   - Escribe: `taskschd.msc`
   - Presiona Enter

2. **Crear nueva tarea**
   - Click derecho en "Task Scheduler Library" → "Create Basic Task"
   - Nombre: `SISOLMAR Queue Worker`
   - Descripción: `Mantiene el worker de Laravel corriendo para procesar matrículas`

3. **Configurar inicio**
   - Trigger: "When the computer starts" (Al iniciar el sistema)
   - Click Next

4. **Configurar acción**
   - Action: "Start a program"
   - Program/script: `C:\Users\Usuario\Desktop\proyect\SISOLMAR\iniciar_queue_worker.bat`
   - Click Next → Finish

5. **Configuración adicional**
   - Click derecho en la tarea creada → Properties
   - Pestaña "General":
     - ☑️ Run whether user is logged on or not
     - ☑️ Run with highest privileges
   - Pestaña "Settings":
     - ☑️ If the task fails, restart every: 1 minute
     - ☑️ Attempt to restart up to: 999 times
     - ☐️ Stop the task if it runs longer than: (desmarcar)
   - Click OK

6. **Probar**
   - Click derecho en la tarea → Run
   - Verificar que aparece una ventana con "Queue Worker - SISOLMAR"

---

### Opción 2: Archivo de Inicio (Más simple pero menos robusto)

1. **Presiona Windows + R**
2. **Escribe:** `shell:startup`
3. **Copia el archivo** `iniciar_queue_worker.bat` a esa carpeta
4. **Reinicia la PC** para probar

---

## 🧪 Verificar que está funcionando

Ejecuta en PowerShell:

```powershell
php artisan tinker --execute="echo 'Jobs en cola: ' . DB::table('sw_jobs')->count();"
```

- Si muestra 0 o pocos jobs → ✅ Funcionando
- Si muestra muchos jobs acumulados → ❌ Worker NO está corriendo

---

## 🔴 ¿Qué pasa si el Worker NO está corriendo?

- ❌ Las matrículas NO se guardan en la BD
- ❌ Los emails NO se envían
- ❌ Los jobs se acumulan en `sw_jobs`
- ⚠️ El usuario ve "Matriculación iniciada" pero nada pasa

---

## 📊 Monitoreo

Para ver si el worker está procesando jobs:

```powershell
# Ver procesos PHP corriendo
Get-Process php

# Ver jobs en cola
php artisan queue:work --once
```

---

## 🛑 Detener el Worker

Si necesitas detenerlo:

```powershell
Stop-Process -Name php -Force
```

---

## ⚙️ Configuración de Producción

El archivo `.env` debe tener:

```env
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=maracuya433@gmail.com
MAIL_PASSWORD=fdnzojwdrypuicbf
MAIL_ENCRYPTION=tls
```

---

## 🚀 Resumen

1. ✅ Configura Task Scheduler con `iniciar_queue_worker.bat` **EN EL SERVIDOR WEB**
2. ✅ Reinicia el servidor/PC
3. ✅ Verifica que el worker está corriendo
4. ✅ Prueba matriculando 2 personas
5. ✅ Confirma que se guardaron en BD y se enviaron emails

**El worker se reiniciará automáticamente:**
- Al arrancar el sistema
- Si se cae por algún error
- Sin intervención manual

---

## 🏢 Escenario de Producción Real

### Configuración típica:

1. **Servidor Web (IIS/Apache)**: Donde está instalada la aplicación Laravel
   - Instalar el `iniciar_queue_worker.bat` aquí
   - Configurar Task Scheduler aquí
   - Este servidor sirve las páginas web

2. **Servidor de Base de Datos**: SQL Server (192.168.10.21)
   - Solo tiene la base de datos
   - NO necesita queue worker
   - La aplicación se conecta remotamente

3. **Clientes**: Computadoras con navegador
   - Solo abren http://tu-servidor/sisolmar
   - NO ejecutan nada de Laravel
   - NO necesitan queue worker

### ⚠️ CRÍTICO:

El queue worker **DEBE ejecutarse en el mismo servidor donde está Laravel**, no importa cuántos clientes se conecten. Es un proceso del servidor, no del cliente.

**Ejemplo:**
- Servidor web en: `192.168.10.50` → **AQUÍ va el queue worker**
- Base de datos en: `192.168.10.21` → Solo SQL Server
- Cliente 1 abre: `http://192.168.10.50/sisolmar` → Solo usa navegador
- Cliente 2 abre: `http://192.168.10.50/sisolmar` → Solo usa navegador
- Todos comparten el mismo queue worker del servidor
