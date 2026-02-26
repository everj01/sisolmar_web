USE [sisolm_web]
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

IF OBJECT_ID('dbo.SW_LISTAR_FOLIOS_PROXIMOS_VENCER_CONSULTAS', 'P') IS NOT NULL
    DROP PROCEDURE dbo.SW_LISTAR_FOLIOS_PROXIMOS_VENCER_CONSULTAS
GO

CREATE PROCEDURE [dbo].[SW_LISTAR_FOLIOS_PROXIMOS_VENCER_CONSULTAS]
(
    @dni VARCHAR(15) = NULL,
    @codCliente CHAR(5) = NULL,
    @codSucursal VARCHAR(10) = NULL,
    @persona VARCHAR(150) = NULL,
    @servicio INT = NULL,
    @dias INT = 30,
    @fecha_desde DATE = NULL,
    @fecha_hasta DATE = NULL
)
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRY
        -- 1. Sucursales habilitadas
        WITH SucursalesCliente AS (
            SELECT DISTINCT LTRIM(RTRIM(S.codigo_sucursal)) as codigo_sucursal
            FROM [192.168.10.20].CONTROLCLIENTES2018.dbo.CABECERA_SERVICIO_2014 S
            WHERE S.estado_servicio = 3
              AND (@codCliente IS NULL OR S.codigo_cliente COLLATE Modern_Spanish_CI_AS = @codCliente)
              AND (@servicio IS NULL OR S.codigo_servicio = @servicio)
        ),
        
        -- 2. Personal Activo (Maestra)
        PersonalActivo AS (
            SELECT
                per.CODI_PERS,
                LTRIM(RTRIM(per.NRO_DOCU_IDEN)) as dni_clean,
                (per.APEL_1 + ' ' + ISNULL(per.APEL_2,'') + ' ' + per.NOMB_1) AS personal,
                per.SUCU_CODIGO,
                per.EMPR_CODIGO,
                per.PERS_FECHEMISIONDNI,
                per.PERS_FECHCADUCADNI
            FROM si_solm.dbo.PERSONAL per
            WHERE per.FECH_CESE IS NULL
              AND (@dni IS NULL OR LTRIM(RTRIM(per.NRO_DOCU_IDEN)) = LTRIM(RTRIM(@dni)))
              AND (@codSucursal IS NULL OR CAST(per.SUCU_CODIGO AS INT) = CAST(@codSucursal AS INT))
              AND (@persona IS NULL OR (per.APEL_1 + ' ' + ISNULL(per.APEL_2,'') + ' ' + per.NOMB_1) LIKE '%' + @persona + '%')
        ),
        
        -- 3. Datos Maestro (Filtros)
        DatosFull AS (
            SELECT 
                pa.*,
                su.SUCU_ABREVIATURA AS sucursal_nombre,
                ISNULL(sc.nombre_cliente, cl_emp.abreviatura) as cliente_nombre,
                fo.FOCO_FEC_EMISION, fo.FOCO_FEC_CADUCA,
                ca.CARN_FEC_EMI, ca.CARN_FEC_FIN,
                li.LICE_FEC_EMI, li.LICE_FEC_CADU
            FROM PersonalActivo pa
            LEFT JOIN sw_MIGRA_SISO_SUCURSAL su ON CAST(su.SUCU_CODIGO AS INT) = CAST(pa.SUCU_CODIGO AS INT)
            OUTER APPLY (
                SELECT TOP 1 c.abreviatura as nombre_cliente
                FROM [192.168.10.20].CONTROLCLIENTES2018.dbo.CABECERA_SERVICIO_2014 S
                INNER JOIN sw_clientes c ON c.cod_legacy COLLATE DATABASE_DEFAULT = LTRIM(RTRIM(S.codigo_cliente)) COLLATE DATABASE_DEFAULT
                WHERE CAST(S.codigo_sucursal AS INT) = CAST(pa.SUCU_CODIGO AS INT)
                  AND S.estado_servicio = 3
                  AND (@codCliente IS NULL OR S.codigo_cliente COLLATE DATABASE_DEFAULT = @codCliente COLLATE DATABASE_DEFAULT)
            ) sc
            LEFT JOIN sw_clientes cl_emp ON 
                pa.EMPR_CODIGO NOT IN ('01', '1') 
                AND cl_emp.cod_legacy COLLATE DATABASE_DEFAULT = LTRIM(RTRIM(pa.EMPR_CODIGO)) COLLATE DATABASE_DEFAULT
            LEFT JOIN si_solm.dbo.FOTO_CONTROL fo ON fo.CODI_PERS = pa.CODI_PERS AND fo.FOCO_VIGENTE = 'SI'
            LEFT JOIN si_solm.dbo.SUCA_CARNE ca ON ca.CODI_PERS = pa.CODI_PERS AND ca.CARN_VIGENCIA = 'SI'
            LEFT JOIN si_solm.dbo.SUCA_LICENCIA_ARMAS li ON li.CODI_PERS = pa.CODI_PERS AND li.LICE_VIGENCIA = 'SI'
            WHERE (@codCliente IS NULL OR sc.nombre_cliente IS NOT NULL OR pa.EMPR_CODIGO NOT IN ('01', '1'))
              AND (@codCliente IS NULL OR @dni IS NOT NULL OR 
                   EXISTS (SELECT 1 FROM SucursalesCliente sc_list WHERE CAST(sc_list.codigo_sucursal AS INT) = CAST(pa.SUCU_CODIGO AS INT))
              )
        ),
        
        FoliosHabilitados AS (
            SELECT codigo AS codFolio, nombre AS documento, 
                CASE WHEN obligatorio = 1 THEN 'PRINCIPAL' ELSE 'ADICIONAL' END AS tipo_folio,
                CASE WHEN obligatorio = 1 THEN 'ALTA' ELSE 'NORMAL' END AS prioridad,
                apto_carga
            FROM sw_folios WHERE habilitado = 1
        ),
        
        UltimoFolio AS (
            SELECT codPersonal, codFolio, fecha_emision, fecha_caducidad
            FROM (
                SELECT sfd.codPersonal, sfd.codFolio, sfd.fecha_emision, sfd.fecha_caducidad, 
                ROW_NUMBER() OVER (PARTITION BY sfd.codPersonal, sfd.codFolio ORDER BY sfd.codigo DESC) AS rn
                FROM sw_folios_detalles sfd
                WHERE sfd.habilitado = 1
            ) t WHERE rn = 1
        ),
        
        FoliosConFechas AS (
            SELECT
                df.sucursal_nombre as sucursal,
                df.CODI_PERS AS codPersonal,
                df.dni_clean as dni,
                df.cliente_nombre as cliente, 
                df.personal,
                fh.codFolio,
                fh.documento,
                fh.tipo_folio,
                fh.prioridad,
                fh.apto_carga,
                CASE
                    WHEN fh.apto_carga = 1 THEN uf.fecha_caducidad
                    WHEN fh.codFolio = 9  THEN df.PERS_FECHCADUCADNI
                    WHEN fh.codFolio = 7  THEN df.FOCO_FEC_CADUCA
                    WHEN fh.codFolio = 6  THEN df.CARN_FEC_FIN
                    WHEN fh.codFolio = 10 THEN df.LICE_FEC_CADU
                    ELSE NULL
                END AS fecha_caducidad
            FROM DatosFull df
            CROSS JOIN FoliosHabilitados fh
            LEFT JOIN UltimoFolio uf ON CAST(uf.codPersonal AS INT) = CAST(df.CODI_PERS AS INT) 
                                     AND uf.codFolio = fh.codFolio
        )
        
        SELECT DISTINCT 
            sucursal, codPersonal, dni, cliente, personal, documento, 
            tipo_folio, prioridad, fecha_caducidad, 0 AS pendiente,
            'por vencer' AS estado
        FROM FoliosConFechas
        WHERE fecha_caducidad IS NOT NULL
          AND fecha_caducidad > GETDATE()
          AND (
               (@fecha_desde IS NULL AND @fecha_hasta IS NULL AND fecha_caducidad <= DATEADD(day, @dias, GETDATE()))
               OR
               (@fecha_desde IS NOT NULL AND @fecha_hasta IS NOT NULL AND CAST(fecha_caducidad AS DATE) BETWEEN @fecha_desde AND @fecha_hasta)
          )
        ORDER BY fecha_caducidad ASC;

    END TRY
    BEGIN CATCH
        SELECT ERROR_MESSAGE() AS mensaje, ERROR_LINE() AS linea;
    END CATCH
END
