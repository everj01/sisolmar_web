<?php
require_once("ClsDatos.Conexion.php");
class CD_DJ
{
	    private $lista;
		
		function Try_Catch($query)
		{
			$query_Try_Catch='BEGIN TRY ';
    		$query_Try_Catch.=$query.'; ';
			$query_Try_Catch.='END TRY ';
			$query_Try_Catch.='BEGIN CATCH ';
    		$query_Try_Catch.='SELECT '; 
        	$query_Try_Catch.='ERROR_NUMBER() AS ErrorNumber ,ERROR_PROCEDURE() AS ErrorProcedure ,ERROR_LINE() AS ErrorLine ,ERROR_MESSAGE() AS ErrorMessage; ';
			$query_Try_Catch.='END CATCH; ';
			
			return $query_Try_Catch;
		}
		
		public function __construct()
		{	
			$this->lista_1=array();
		}

		public function DJ_List_Datos_Personales($CODI_PERS,$CODI_EMPRESA)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_3();
			$strSQL=$this->Try_Catch("EXECUTE usp_Persona_Borrador '".$CODI_PERS."','".$CODI_EMPRESA."'");
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}
		
		public function DJ_List_Nacimiento($CODI_PERS,$CODI_EMPRESA)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_3();
			$strSQL=$this->Try_Catch("EXECUTE DBO.SP_LUGAR_NACI_PERSONAL '".$CODI_PERS."','".$CODI_EMPRESA."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}

		public function DJ_Conyugue($CODI_PERS)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_3();
			$strSQL=$this->Try_Catch("SELECT CONCAT(APEL_1, ' ', APEL_2, ', ', NOMB_1, ' ', NOMB_2) AS Nombres
									, DEHA_OCUPACION AS OCUPACION
									, CONVERT (CHAR(10),FECH_NACI,105) AS FECH_NACI
									 FROM SI_SOLM.DBO.DERECHO_HABIENTE
									 WHERE 
									 (TIPO_RELA = 'CONVIVIENTE' OR TIPO_RELA = 'CONYUGE')
									 and CODI_PERS = '".$CODI_PERS."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}
		
		public function DJ_Hijos($CODI_PERS)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_3();
			$strSQL=$this->Try_Catch("SELECT CONCAT(APEL_1, ' ', APEL_2, ', ', NOMB_1, ' ', NOMB_2) AS Nombres
									, DEHA_OCUPACION AS OCUPACION
									, CONVERT (CHAR(10),FECH_NACI,105) AS FECH_NACI
									 FROM SI_SOLM.DBO.DERECHO_HABIENTE
									 WHERE 
									 TIPO_RELA = 'HIJO'
									 and CODI_PERS = '".$CODI_PERS."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}
		
		public function DJ_Padre($CODI_PERS,$CODI_EMPRESA)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_3();
			if ($CODI_EMPRESA=='05'){
				$strSQL=$this->Try_Catch("SELECT CODI_DERE_HABI, CONCAT(APEL_1, ' ', APEL_2, ', ', NOMB_1, ' ', NOMB_2) AS Nombres
									, DEHA_OCUPACION AS OCUPACION
									, TIPO_RELA
									, NOMB_1
									, NOMB_2
									, APEL_1
									, APEL_2
									, CONVERT (CHAR(10),FECH_NACI,105) AS FECH_NACI
									 FROM SI_SOLM_SUPPLY.DBO.DERECHO_HABIENTE
									  WHERE
									CODI_PERS = '".$CODI_PERS."' AND TIPO_RELA IN ('PADRE','MADRE','Conyuge','Hijo','HERMANO')
									ORDER BY TIPO_RELA 
									  ");		
			}else{
				$strSQL=$this->Try_Catch("SELECT CODI_DERE_HABI, CONCAT(APEL_1, ' ', APEL_2, ', ', NOMB_1, ' ', NOMB_2) AS Nombres
									, DEHA_OCUPACION AS OCUPACION
									, TIPO_RELA
									, NOMB_1
									, NOMB_2
									, APEL_1
									, APEL_2
									, CONVERT (CHAR(10),FECH_NACI,105) AS FECH_NACI
									 FROM SI_SOLM.DBO.DERECHO_HABIENTE
									  WHERE
									CODI_PERS = '".$CODI_PERS."' AND TIPO_RELA IN ('PADRE','MADRE','Conyuge','Hijo','HERMANO')
									ORDER BY TIPO_RELA 
									  ");		

			}
			
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}
		
		public function DJ_Madre($CODI_PERS)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_3();
			$strSQL=$this->Try_Catch("SELECT CONCAT(APEL_1, ' ', APEL_2, ', ', NOMB_1, ' ', NOMB_2) AS Nombres
									, DEHA_OCUPACION AS OCUPACION
									, CONVERT (CHAR(10),FECH_NACI,105) AS FECH_NACI
									 FROM SI_SOLM.DBO.DERECHO_HABIENTE
									  WHERE
									CODI_PERS = '".$CODI_PERS."'
									  and TIPO_RELA = 'MADRE'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}
		
		public function DJ_Hermano($CODI_PERS)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_3();
			$strSQL=$this->Try_Catch("SELECT CONCAT(APEL_1, ' ', APEL_2, ', ', NOMB_1, ' ', NOMB_2) AS Nombres
									, DEHA_OCUPACION AS OCUPACION
									, CONVERT (CHAR(10),FECH_NACI,105) AS FECH_NACI
									 FROM SI_SOLM.DBO.DERECHO_HABIENTE
									  WHERE
									CODI_PERS = '".$CODI_PERS."'
									  and TIPO_RELA = 'HERMANO'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}
		
		public function DJ_reg_solicitud_actualizacion($CODI_SIP)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("INSERT INTO dbo.SIP_Sol_Actualizacion_DJ(sol_CODI_SIP,sol_fecha) VALUES('".$CODI_SIP."',GETDATE());SELECT SCOPE_IDENTITY() AS rpta");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			if($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC))
			{
				$rpta=$reg["rpta"];
			}
			sqlsrv_close($con);
			return $rpta;
		}
		
		public function DJ_solicitud_actualizacion_nomb_1($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_NOMB_1='".strtoupper(utf8_decode($propuesta))."',sol_NOMB_1_prev='".utf8_decode(strtoupper($actual))."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_nomb_2($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_NOMB_2='".utf8_decode(strtoupper($propuesta))."',sol_NOMB_2_prev='".utf8_decode(strtoupper($actual))."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_apel_1($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_APEL_1='".utf8_decode(strtoupper($propuesta))."',sol_APEL_1_prev='".utf8_decode(strtoupper($actual))."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_apel_2($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_APEL_2='".utf8_decode(strtoupper($propuesta))."',sol_APEL_2_prev='".utf8_decode(strtoupper($actual))."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_dni($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_NRO_DOCU_IDEN='".strtoupper($propuesta)."',sol_NRO_DOCU_IDEN_prev='".strtoupper($actual)."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_tel($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_PERS_TELEFONO='".$propuesta."',sol_PERS_TELEFONO_prev='".$actual."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_tel_emer($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_PERS_NROEMERGENCIA='".$propuesta."',sol_PERS_NROEMERGENCIA_prev='".$actual."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_cont_emer($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_PERS_NOMCONTACTO='".utf8_decode(strtoupper($propuesta))."',sol_PERS_NOMCONTACTO_prev='".utf8_decode(strtoupper($actual))."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_email($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_PRES_EMAIL='".strtolower($propuesta)."',sol_PRES_EMAIL_prev='".strtolower($actual)."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_dist_naci($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_DIST_NACI='".strtolower($propuesta)."',sol_DIST_NACI_prev='".strtolower($actual)."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_dist_direccion($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_DISTRITO='".strtolower($propuesta)."',sol_DISTRITO_prev='".strtolower($actual)."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_direccion($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_DIRECCION='".strtolower($propuesta)."',sol_DIRECCION_prev='".strtolower($actual)."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_Estado_Civil($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_ESCI_CODIGO='".strtolower($propuesta)."',sol_ESCI_CODIGO_prev='".strtolower($actual)."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitud_actualizacion_profesion($propuesta,$actual,$id_reg)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_PERS_PROFESION='".strtolower($propuesta)."',sol_PERS_PROFESION_prev='".strtolower($actual)."' WHERE id_solicitud='".$id_reg."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		
		
		public function DJ_solicitud_actualizacion_DEHA($id_sol,$codi_deha,$ocupacion,$ocupacion_prev,$nomb1,$nomb1_prev,$nomb2,$nomb2_prev,$apel1,$apel1_prev,$apel2,$apel2_prev)
		{

			if ($ocupacion=='')
			{
				$ocupacion_e='NULL';
				$ocupacion_prev_e='NULL';
			}
			else
			{
				$ocupacion_e="'".$ocupacion."'";
				$ocupacion_prev_e="'".$ocupacion_prev."'";
			}
			
			if ($nomb1=='')
			{
				$nomb1_e='NULL';
				$nomb1_prev_e='NULL';
			}
			else
			{
				$nomb1_e="'".strtoupper ($nomb1)."'";
				$nomb1_prev_e="'".strtoupper ($nomb1_prev)."'";
			}
			
			if ($nomb2=='')
			{
				$nomb2_e='NULL';
				$nomb2_prev_e='NULL';
			}
			else
			{
				$nomb2_e="'".strtoupper ($nomb2)."'";
				$nomb2_prev_e="'".strtoupper ($nomb2_prev)."'";
			}
			
			if ($apel1=='')
			{
				$apel1_e='NULL';
				$apel1_prev_e='NULL';
			}
			else
			{
				$apel1_e="'".strtoupper ($apel1)."'";
				$apel1_prev_e="'".strtoupper ($apel1_prev)."'";
			}
			
			if ($apel2=='')
			{
				$apel2_e='NULL';
				$apel2_prev_e='NULL';
			}
			else
			{
				$apel2_e="'".strtoupper ($apel2)."'";
				$apel2_prev_e="'".strtoupper ($apel2_prev)."'";
			}

			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL="INSERT INTO dbo.SIP_Sol_DERECHO_HABIENTE_Actualizacion(sol_id_solicitud,CODI_DERE_HABI,sol_DEHA_OCUPACION,sol_DEHA_OCUPACION_prev,sol_NOMB_1,sol_NOMB_1_prev,sol_NOMB_2,sol_NOMB_2_prev,sol_APEL_1,sol_APEL_1_prev,sol_APEL_2,sol_APEL_2_prev)VALUES('".$id_sol."','".$codi_deha."',".$ocupacion_e.",".$ocupacion_prev_e.",".utf8_decode($nomb1_e).",".utf8_decode($nomb1_prev_e).",".utf8_decode($nomb2_e).",".utf8_decode($nomb2_prev_e).",".utf8_decode($apel1_e).",".utf8_decode($apel1_prev_e).",".utf8_decode($apel2_e).",".utf8_decode($apel2_prev_e).")";		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_listar_solicitudes_act($codi_sip)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("exec SP_SIP_DJ_DEHA_ACTUALIZAR_DATOS_LISTA '".$codi_sip."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}
		
		public function DJ_solicitudes_act_det($codi_sip,$sol_id)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("exec SP_SIP_DJ_DEHA_ACTUALIZAR_DATOS_DET '".$_SESSION['USER_SIP_id']."','".$sol_id."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}
		
		public function DJ_solicitudes_act_det_resp($sol_id)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("exec SP_SIP_DJ_DEHA_ACTUALIZAR_DATOS_RESP '".$sol_id."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}
		
		public function DJ_solicitudes_act_det_grabar_lectura($codi_sip,$id_sol)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("EXEC SP_SIP_DJ_ACT_GRABAR_LEIDO '".$codi_sip."','".$id_sol."'");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitudes_act_estado($id_sol,$estado,$codi_sip)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE dbo.SIP_Sol_Actualizacion_DJ SET sol_estado=".$estado.",sol_fecha_mod=GETDATE(),sol_CODI_SIP_mod='".$codi_sip."' WHERE id_solicitud=".$id_sol."");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			return $res;
		}
		
		public function DJ_solicitudes_grabar_respuesta($id_sol,$codi_sip,$observacion)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("INSERT INTO dbo.SIP_Sol_Actualizacion_DJ_resp(id_solicitud,sol_resp_codi_sip,sol_resp_observacion,sol_resp_fecha) VALUES('".$id_sol."','".$codi_sip."','".(utf8_decode($observacion))."',GETDATE());SELECT SCOPE_IDENTITY() AS ID_REG");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			if( $res === false) {
				die( print_r( sqlsrv_errors(), true) );
			}
			sqlsrv_close($con);
			if($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC))
			{
				$rpta=$reg["ID_REG"];
			}
			sqlsrv_close($con);
			return $rpta;
		}	
		
		
		public function DJ_email_update($userid,$email)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_4();
			$strSQL=$this->Try_Catch("UPDATE PERSONAL SET PERS_EMAIL='".$email."' WHERE (CODI_PERS = '".$userid."')");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL");
			sqlsrv_close($con);
			
		}
		//Actualizacion de datos
		public function DJ_update_datos($userid,$correo,$celular,$direccion,$brevete=NULL,$numBrevete=NULL,$categoria=NULL,$clase=NULL,$numEmergencia,$nombreEmergencia)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			if($_SESSION['USER_Empresa_cod'] == '05'){
				$con=Conexion::Abrir_Conexion_7();
				$strSQL=$this->Try_Catch("UPDATE PERSONAL SET PERS_EMAIL='".$correo."', DIRECCION='".$direccion."', PERS_TELEFONO='".$celular."', PERS_BREVETE='".$numBrevete."', CATEGORIA_BREVETE='".$categoria."', CLASE_BREVETE='".$clase."', PERS_NROEMERGENCIA='".$numEmergencia."', PERS_NOMCONTACTO='".$nombreEmergencia."' WHERE (CODI_PERS = '".$userid."')");		
			}else{
				$con=Conexion::Abrir_Conexion_4();
				$strSQL=$this->Try_Catch("UPDATE PERSONAL SET PERS_EMAIL='".$correo."', DIRECCION='".$direccion."', PERS_TELEFONO='".$celular."', PERS_CONBREVETE='".$brevete."', PERS_BREVETE='".$numBrevete."', CATEGORIA_BREVETE='".$categoria."', CLASE_BREVETE='".$clase."', PERS_NROEMERGENCIA='".$numEmergencia."', PERS_NOMCONTACTO='".$nombreEmergencia."' WHERE (CODI_PERS = '".$userid."')");		
			}
			//echo $strSQL;
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL1");
			sqlsrv_close($con);

			
		}
		public function DJ_update_datos_SIP($userid_sip,$correo)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE Seguridad_UsuarioSIP SET actualizacion_datos='1', usuarioSIP_correo='".$direccion."', modificado_por='".$userid_sip."', fecha_modificacion=GETDATE() WHERE (usuarioSIP_id = '".$userid_sip."')");		
			//echo $strSQL;
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL2");
			sqlsrv_close($con);
		}
		public function DJ_update_datos_SIP_EQUAL($userid_sip)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("UPDATE Seguridad_UsuarioSIP SET actualizacion_datos='1', modificado_por='".$userid_sip."', fecha_modificacion=GETDATE() WHERE (usuarioSIP_id = '".$userid_sip."')");		
			//echo $strSQL;
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL2");
			sqlsrv_close($con);
		}

		public function DJ_update_datos_BITACORA_OLD($userid)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("INSERT INTO dbo.Seguridad_Bitacora_Actualizacion (correo, celular, direccion, brevete, fonoEmergencia, contacto, habilitado, fecha_creacion, periodo, tipo, codi_pers) VALUES ('".$_SESSION['USER_Correo']."','".$_SESSION['USER_Phone']."','".$_SESSION['USER_Direccion']."','".$_SESSION['USER_ConBrevete']."','".$_SESSION['USER_NroEmergencia']."','".$_SESSION['USER_NomEmergencia']."',1,GETDATE(),1,'OLD','".$userid."')");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL3");
			sqlsrv_close($con);
		}
		public function DJ_update_datos_BITACORA_NEW_EQUAL($userid)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("INSERT INTO dbo.Seguridad_Bitacora_Actualizacion (correo, celular, direccion, brevete, fonoEmergencia, contacto, habilitado, fecha_creacion, periodo, tipo, codi_pers) VALUES ('".$_SESSION['USER_Correo']."','".$_SESSION['USER_Phone']."','".$_SESSION['USER_Direccion']."','".$_SESSION['USER_ConBrevete']."','".$_SESSION['USER_NroEmergencia']."','".$_SESSION['USER_NomEmergencia']."',1,GETDATE(),1,'NEW','".$userid."')");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL3");
			sqlsrv_close($con);
		}
		public function DJ_update_datos_BITACORA_NEW($userid,$correo,$celular,$direccion,$brevete,$numBrevete,$categoria,$clase,$numEmergencia,$nombreEmergencia)
		{
			$this->lista_1=array();//--------------LIMPIANDO ARRAY -----------------------------
			$con=Conexion::Abrir_Conexion_1();
			$strSQL=$this->Try_Catch("INSERT INTO dbo.Seguridad_Bitacora_Actualizacion (correo, celular, direccion, brevete, fonoEmergencia, contacto, habilitado, fecha_creacion, periodo, tipo, codi_pers) VALUES ('".$correo."','".$celular."','".$direccion."','".$brevete."','".$numEmergencia."','".$nombreEmergencia."',1,GETDATE(),1,'NEW','".$userid."')");		
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta SQL4");
			sqlsrv_close($con);
		}

		public function Listar_Grados_Instruccion()
		{
			$this->lista_1=array();
			$con=Conexion::Abrir_Conexion_4();
			$strSQL="EXECUTE dbo.SSP_RH_GRADOS_INSTRUCCION_LISTAR";
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta Grados");
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}

		public function Listar_Carreras()
		{
			$this->lista_1=array();
			$con=Conexion::Abrir_Conexion_4();
			// Se usa el nombre de tabla proporcionado por el usuario con alias para compatibilidad JS
			$strSQL="SELECT CARR_CODIGO AS CARRERA_CODIGO, CARR_DESCRIPCION AS CARRERA_DESC FROM [si_solm].[dbo].[SUNAT_CARRERAS] ORDER BY CARR_DESCRIPCION";
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta Carreras");
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}

		public function Listar_Carreras_Por_Institucion($iedu_codigo)
		{
			$this->lista_1=array();
			$con=Conexion::Abrir_Conexion_4();
			$strSQL="SELECT CARR_CODIGO AS CARRERA_CODIGO, CARR_DESCRIPCION AS CARRERA_DESC FROM [si_solm].[dbo].[SUNAT_CARRERAS] WHERE IEDU_CODIGO = '".$iedu_codigo."' ORDER BY CARR_DESCRIPCION";
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta Carreras por Inst");
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}

		public function Listar_Instituciones()
		{
			$this->lista_1=array();
			$con=Conexion::Abrir_Conexion_4();
			// Se usa el nombre de tabla proporcionado por el usuario con alias para compatibilidad JS
			$strSQL="SELECT IEDU_CODIGO AS INST_CODIGO, IEDU_DESCRIPCION AS INST_DESC FROM [si_solm].[dbo].[SUNAT_IEDUCATIVA] ORDER BY IEDU_DESCRIPCION";
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta Instituciones");
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}

		public function Get_Personal_Data_Completo($CODI_PERS)
		{
			$this->lista_1=array();
			$con=Conexion::Abrir_Conexion_4();
			$strSQL="EXECUTE dbo.SSP_PERSONAL_DATOS_DJ_MOSTRAR '".$CODI_PERS."'";
			$res=sqlsrv_query($con,$strSQL) or die("Error en la consulta Personal Completo");
			if($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				sqlsrv_close($con);
				return $reg;
			}
			sqlsrv_close($con);
			return null;
		}

		public function Listar_Departamentos()
		{
			$this->lista_1=array();
			$con=Conexion::Abrir_Conexion_4();
			$strSQL="SELECT DISTINCT DEPT_NOMB AS id, DEPT_NOMB AS text FROM dbo.UBICACION ORDER BY DEPT_NOMB";
			$res=sqlsrv_query($con,$strSQL);
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}

		public function Listar_Provincias($DEPT_NOMB)
		{
			$this->lista_1=array();
			$con=Conexion::Abrir_Conexion_4();
			$strSQL="SELECT DISTINCT PROV_NOMB AS id, PROV_NOMB AS text FROM dbo.UBICACION WHERE DEPT_NOMB = '".$DEPT_NOMB."' ORDER BY PROV_NOMB";
			$res=sqlsrv_query($con,$strSQL);
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}

		public function Listar_Distritos($DEPT_NOMB, $PROV_NOMB)
		{
			$this->lista_1=array();
			$con=Conexion::Abrir_Conexion_4();
			$strSQL="SELECT DISTINCT DIST_NOMB AS id, DIST_NOMB AS text FROM dbo.UBICACION WHERE DEPT_NOMB = '".$DEPT_NOMB."' AND PROV_NOMB = '".$PROV_NOMB."' ORDER BY DIST_NOMB";
			$res=sqlsrv_query($con,$strSQL);
			while($reg = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
				$this->lista_1[]=$reg;
			}
			sqlsrv_close($con);
			return $this->lista_1;
		}

}
?>
