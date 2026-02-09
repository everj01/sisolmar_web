<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class FileControl extends Model
{
    use HasFactory;

    public static function getPersonal()
    {
        $sucursal = 0;
        return DB::select('EXEC SW_LISTAR_PERSONAL_X_SUCURSAL ?', [$sucursal]);
    }

    public static function getPersonalTotal(Request $request)
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 50);
        $search = $request->get('search', null);
        $tipo_per = $request->get('tipo_per', null);
        $vigencia = $request->get('vigencia', null);
        $codSucursal = $request->get('codSucursal', '0');

        // SP de datos
        $data = DB::select('EXEC SW_LISTAR_PERSONAL_X_SUCURSAL_TOTAL ?, ?, ?, ?, ?, ?', [
            $codSucursal, $page, $size, $search, $tipo_per, $vigencia
        ]);

        // SP de total
        $total = DB::select('EXEC SW_CONTAR_PERSONAL ?, ?, ?, ?', [
            $codSucursal, $search, $tipo_per, $vigencia
        ])[0]->total;

        return response()->json([
            'data' => $data,
            'last_page' => ceil($total / $size),
            'total' => (int) $total,
        ]);
    }

    public static function getPersonalTotalPrueba(Request $request)
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 50);
        $search = $request->get('search', null);
        $tipo_per = $request->get('tipo_per', null);
        $vigencia = $request->get('vigencia', null);
        $codSucursal = $request->get('codSucursal', '0');

        // SP de datos
        $data = DB::select('EXEC SW_LISTAR_PERSONAL_X_SUCURSAL_TOTAL_PRUEBA ?, ?, ?, ?', [
            $codSucursal, $search, $tipo_per, $vigencia
        ]);

        // SP de total
        $total = DB::select('EXEC SW_CONTAR_PERSONAL ?, ?, ?, ?', [
            $codSucursal, $search, $tipo_per, $vigencia
        ])[0]->total;

        return response()->json([
            'data' => $data,
            'last_page' => ceil($total / $size),
            'total' => (int) $total,
        ]);
    }

    public static function getDocsXPersona($codPersonal = '1')
    {
        return DB::select('EXEC SW_LISTAR_FOLIOS_X_PERSONAL ?', [$codPersonal]);
    }

    public static function getClientes()
    {
        return DB::select('EXEC SW_LISTAR_CLIENTES');
    }

    public static function getClientesLegajos()
    {
        return DB::select('EXEC SW_LISTAR_CLIENTES_LEGAJOS');
    }

    public static function getCargosXCliente($cliente)
    {
        return DB::select('EXEC SW_LISTAR_CARGOS_X_CLIENTES_LEGAJOS ?', [$cliente]);
    }

    public static function getSucursales()
    {
        return DB::select('EXEC SW_LISTAR_SUCURSALES');
    }

    public static function getCargos()
    {
        return DB::select('EXEC SW_LISTAR_CARGOS');
    }

    public static function getLegajos($cliente, $cargo, $codPersonal)
    {
        return DB::select('EXEC SW_LISTAR_LEGAJOS_X_PERSONA ?, ?, ?', [$cliente, $cargo, $codPersonal]);
    }

    public static function getFolios()
    {
        return DB::select('EXEC SW_LISTAR_FOLIOS');
    }

    public static function getAllFolios()
    {
        return DB::select('EXEC SW_LISTAR_FOLIOS');
    }

    /*public static function saveCargo(Request $request){
        DB::table('sw_cargos')->insert([
            'descripcion' => $request->nombre,
            'creado_por' => 'SOPORTE_SW',
            'fecha_creacion' => now(),
        ]);

        return response()->json(['mensaje' => 'Producto agregado con éxito'], 201);
    }*/

    public static function saveSolicitud($codigo, $tiene, $cargo, $cliente, $creado_por){
        return DB::statement('EXEC SW_SAVE_SOLICITUD ?, ?, ?, ?, ?', [$codigo, $cliente, $cargo, $tiene, $creado_por]);
    }

    public static function getPeriodos()
    {
        return DB::select('EXEC SW_LISTAR_PERIODOS');
    }

    public static function getCoincidencias($cliente, $cargo)
    {
        return DB::select('EXEC SW_LISTAR_COINCIDENCIAS_LEGAJOS ?, ?', [$cliente, $cargo]);
    }
    public static function getFoliosClienteCargo($cliente, $cargo)
    {
        return DB::select('EXEC SW_LISTAR_LEGAJOS_X_CLIENTE_X_CARGO ?, ?', [$cliente, $cargo]);
    }

    //GUARDAR DATOS DE LOS FOLIOS
    public static function saveFolioPersonal($fecha_emision, $fecha_caducidad, $codFolio, $codPersonal/*, $filePath*/)
    {
        // Insertar directamente en la tabla 'folios' usando el Query Builder
        $inserted = DB::table('sw_folios_detalles')->insert([
            'fecha_emision' => $fecha_emision,
            'fecha_caducidad' => $fecha_caducidad,
            'codFolio' => $codFolio,
            'codPersonal' => $codPersonal,
            //'ruta_archivo' => $filePath
        ]);

        return $inserted;
    }
    public static function saveFolio($nombre, $tipo, $obligatorio, $vencimiento, $tipo_fecha, $plataforma)
    {
        // Insertar directamente en la tabla 'folios' usando el Query Builder
        $inserted = DB::table('sw_folios')->insert([
            'nombre' => $nombre,
            'tipo' => $tipo,
            'obligatorio' => $obligatorio,
            'vencimiento' => $vencimiento,
            'tipo_fecha' => $tipo_fecha,
            'plataforma' => $plataforma,
        ]);

        return $inserted;
    }
    public static function updateFolio($codigo, $nombre, $tipo, $obligatorio, $vencimiento, $tipo_fecha, $plataforma)
    {
        // Insertar directamente en la tabla 'folios' usando el Query Builder
        $updated = DB::table('sw_folios')
            ->where('codigo', $codigo)
            ->update([
                'nombre' => $nombre,
                'tipo' => $tipo,
                'obligatorio' => $obligatorio,
                'vencimiento' => $vencimiento,
                'tipo_fecha' => $tipo_fecha,
                'plataforma' => $plataforma,
                'fecha_modificacion' => DB::raw('GETDATE()'),
            ]);

        return $updated;
    }
    public static function disabledFolio($codigo)
    {
        return DB::table('sw_folios')
            ->where('codigo', $codigo)
            ->update([
                'habilitado' => 0,
                'fecha_modificacion' => DB::raw('GETDATE()'),
            ]);
    }

    public static function activarFolio($codigo)
    {
        return DB::table('sw_folios')
            ->where('codigo', $codigo)
            ->update([
                'habilitado' => 1,
                'fecha_modificacion' => DB::raw('GETDATE()'),
            ]);
    }


    public static function saveCargo($descripcion, $nombre, $abreviatura, $cod_servicio, $cod_subservicio, $cod_tipo, $cod_area, $usuario){
        $inserted = DB::table('sw_cargos')->insert([
            'descripcion' => $descripcion,
            'nombre' => $nombre,
            'abreviatura' => $abreviatura,
            'cod_servicio' => $cod_servicio,
            'cod_subservicio' => $cod_subservicio,
            'cod_tipo' => $cod_tipo,
            'cod_area' => $cod_area,
            'creado_por' => $usuario
        ]);
        return $inserted;
    }

    // public static function validarLegajo($folio, $codCliente, $codCargo, $codLegajo){
    //     return DB::select('SELECT TOP 1 * FROM sw_legajos_detalles WHERE codFolio = ? AND codCliente = ? AND codCargo = ? AND habilitado = 1 AND codLegajo = ?', [$folio, $codCliente, $codCargo, $codLegajo]);
    // }

    public static function validarLegajo($folio, $codCliente, $codCargo, $codLegajo = null) {
        return DB::table('sw_legajos_detalles')
            ->where('codFolio', $folio)
            ->where('codCliente', $codCliente)
            ->where('codCargo', $codCargo)
            ->where('codLegajo', $codLegajo)
            //->where('habilitado', 1)
            ->first(); // Esto ya hace TOP 1 en SQL Server
    }


    public static function QuitarTodosLegajos($codLegajo, $usuario)
    {
        // 1. Obtener los codFolio de los registros que serán actualizados
        $folios = DB::table('sw_legajos_detalles')
            ->where('codLegajo', $codLegajo)
            ->where('habilitado', 1) // solo los habilitados, si aplica
            ->pluck('codFolio')
            ->toArray(); // opcional, para tener un array plano

        // 2. Si no hay folios, evitar actualizar
        if (empty($folios)) {
            return [];
        }

        // 3. Ejecutar la actualización
        DB::table('sw_legajos_detalles')
            ->where('codLegajo', $codLegajo)
            ->whereIn('codFolio', $folios)
            ->update([
                'habilitado' => 0,
                //'modificado_por' => $usuario,
                'fecha_modificacion' => DB::raw('GETDATE()')
            ]);

        // 4. Retornar los folios que fueron afectados
        return $folios;
    }

    // public static function saveLegajoMain($nombre){
    //     return DB::select('EXEC SW_INSERTAR_LEGAJO_MAIN ?', [$nombre]);
    // }

    public static function saveLegajoMain($nombre){
        if(empty(trim($nombre))) {
            throw new \InvalidArgumentException('El nombre no puede estar vacío.');
        }
        return DB::table('sw_legajos')->insertGetId([
            'descripcion' => $nombre,
            'habilitado' => 1,
            'creado_por' => session('usuario'),
            'fecha_creacion' => DB::raw('GETDATE()'),
        ]);
    }




    // public static function saveLegajo($folio, $codCliente, $codCargo, $codLegajo){
    //     return DB::select('EXEC SW_INSERTAR_LEGAJO_DETALLE ?, ?, ?, ?', [$codCliente, $codCargo, $codLegajo, $folio]);
    // }

    public static function saveLegajo($folio, $codCliente, $codCargo, $codLegajo, $usuario){
        return DB::table('sw_legajos_detalles')->insert([
            'codCliente' => $codCliente,
            'codCargo'   => $codCargo,
            'codLegajo'  => $codLegajo,
            'codFolio'   => $folio,
            'habilitado' => 1,
            'creado_por' => $usuario,
            'fecha_creacion' => DB::raw('GETDATE()')
        ]);
    }

    public static function updateLegajo($folio, $codCliente, $codCargo, $codLegajo, $usuario){
       return DB::table('sw_legajos_detalles')
        ->where('codCliente', $codCliente)
        ->where('codCargo', $codCargo)
        ->where('codLegajo', $codLegajo)
        ->where('codFolio', $folio)
        ->update([
            'habilitado' => 1,
            'modificado_por' => $usuario,
            'fecha_modificacion' => DB::raw('GETDATE()')
        ]);
    }

    public static function actualizarNotificacion($folio, $codCliente, $codCargo){
        return DB::table('sw_solicitud_cargo_comercial')
            ->where('codCliente', $codCliente)
            ->where('codCargo', $codCargo)
            ->where('codFolio', $folio)
            ->where('habilitado', 1)
            ->where('tipoSolicitar', 0)
            //->where('fecha_creacion', '>=', DB::raw('DATEADD(HOUR, -24, GETDATE())'))
            ->update([
                'listo' => 1,
                'fecha_modificacion' => DB::raw('GETDATE()')
            ]);
    }

    public static function actualizarNotificacionDes($folio, $codCliente, $codCargo){
        $existe = DB::table('sw_solicitud_cargo_comercial')
            ->where('codCliente', $codCliente)
            ->where('codCargo', $codCargo)
            ->where('codFolio', $folio)
            ->where('habilitado', 1)
            ->where('tipoSolicitar', 1)
            ->where('fecha_creacion', '>=', DB::raw('DATEADD(HOUR, -24, GETDATE())'))
            ->exists();

        if (!$existe) {
            return false;
        }

        return DB::table('sw_solicitud_cargo_comercial')
            ->where('codCliente', $codCliente)
            ->where('codCargo', $codCargo)
            ->where('codFolio', $folio)
            ->where('habilitado', 1)
            ->where('tipoSolicitar', 1)
            ->where('fecha_creacion', '>=', DB::raw('DATEADD(HOUR, -24, GETDATE())'))
            ->update([
                'listo' => 1,
                'fecha_modificacion' => DB::raw('GETDATE()')
            ]);
    }


    public static function listarNotificaciones(){
        return DB::select('EXEC SW_LISTAR_NOTIFICACIONES');
    }

    public static function getFoliosXLegajo($codCliente, $codCargo){
        return DB::select('EXEC SW_LISTAR_FOLIOS_X_LEGAJO ?, ?' , [$codCliente, $codCargo]);
    }

    public static function getAreas(){
        return DB::select('EXEC SW_LISTAR_AREAS ');
    }

    public static function getPosicion(){
        return DB::select('EXEC SW_LISTAR_POSICION ');
    }

    public static function getGrupo(){
        return DB::select('EXEC SW_LISTAR_GRUPO ');
    }

    public static function getGrupoId($codigo){
        return DB::select('EXEC SW_LISTAR_GRUPO_ID ? ', [$codigo]);
    }

    public static function insertarCargo($data){
        return DB::statement('EXEC SW_INSERTAR_CARGO ?, ?, ?, ?, ?, ?, ?, ?', $data);
    }

    public static function activarCargo($codigo, $data){
        return DB::statement('EXEC SW_ACTIVAR_CARGO ?, ?', [$codigo, $data]);
    }

    public static function updateCargo($data){
        return DB::statement('EXEC SW_UPDATE_CARGO ?, ?, ?, ?, ?, ?, ?, ?, ?', $data);
    }

    public static function getCargosXCodigo($codigo){
        return DB::select('EXEC SW_LISTAR_CARGOS_X_CODIGO ?', [$codigo]);
    }

    public static function getRutaFolio($codFolio){
        $resultados = DB::select('EXEC SW_RUTA_ARCHIVO_FOLIO ?', [$codFolio]);

        if (!$resultados || count($resultados) === 0) {
            return false;
        }

        $rutas = array_map(function ($item) {
            return $item->ruta;
        }, $resultados);

        return $rutas;
    }

    public static function getViewPerDocs($codPersonal, $codFolio){
        $resultado = DB::select('EXEC SW_DOCS_PER_VIEW ?, ?', [$codPersonal, $codFolio]);
        return $resultado;
    }



    //BUSQUEDA DE LEGAJOS
    public static function getPersonalLegajos(){
        return DB::select('EXEC SW_LISTAR_COINCIDENCIAS_LEGAJOS_GENERAL');
    }

    /* ====================== PARA EL GENERADOR DE PDF ======================= */
    public static function getFoliosInfoPersona($codPersona, $codFolio){
        return DB::select('EXEC SW_LISTAR_DATOS_FOLIO_X_PERSONA ?, ?', [$codPersona, $codFolio]);
    }

    public static function listarPermisos($codigo){
        return DB::statement('EXEC SW_LISTAR_PERMISOS ?', [$codigo]);
    }

    public static function getFoliosXLegajo_comercial($codCliente, $codCargo){
        return DB::select('EXEC SW_LISTAR_FOLIOS_COMERCIAL ?, ?' , [$codCargo, $codCliente]);
    }

    public static function getSucursalXPersona($codPersona){
        $resultado = DB::select('EXEC SW_SUCURSAL_X_PERSONA ?', [$codPersona]);
        return $resultado ? $resultado[0]->sucursal : null;
    }

    public static function getReporteFiltro($codigo){
        return DB::select('EXEC SW_SISOLMAR_REPORTES ?', [$codigo]);
    }

    public static function getPersonalXId($codigo) {
        return DB::table('sw_MIGRA_PERSONAL')
            ->where('CODI_PERS', $codigo)
            ->first();
    }
}
