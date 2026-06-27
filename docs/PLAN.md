# Plan de Orquestación: Reparación de Fechas en Folios Pendientes

## Análisis del Problema
En el stored procedure `SW_LISTAR_FOLIOS_PENDIENTES_CONSULTAS`, el cruce de datos para obtener las fechas de caducidad desde `sw_folios_detalles` está fallando, provocando que los registros salgan como `SIN REGISTRO`.

El problema se debe a la forma en la que se está haciendo el `JOIN` en el CTE `UltimoFolio`. El query intenta hacer un `INNER JOIN` con la tabla maestra `PERSONAL` solo para obtener el `NRO_DOCU_IDEN` (dni) y usarlo para cruzar, pero la condición de join `per_doc.CODI_PERS = sfd.codPersonal` sufre de problemas de tipo de dato o espacios (padding char/varchar con ceros a la izquierda), lo que rompe la relación para ciertas personas (ej: los que tienen ceros a la izquierda como `00023`).

## Solución Propuesta (Fase 2 de Orquestación)

1. **Agente `database-architect`**: Modificar el stored procedure `SW_LISTAR_FOLIOS_PENDIENTES_CONSULTAS` para **eliminar el join innecesario** a la tabla `PERSONAL` dentro de `UltimoFolio`.
2. Haremos el cruce directamente entre `FoliosConFechas` (que ya contiene los datos de la persona) y `UltimoFolio` utilizando `codPersonal` y `CODI_PERS` convertidos a enteros `CAST(... AS INT)` para evadir cualquier problema de padding o caracteres en blanco.
3. El código del CTE `UltimoFolio` quedará mucho más limpio y eficiente, mejorando el performance general de la consulta.

### Cambios en SP:
```sql
        -- CTE Simplificado: Ya no cruzamos con PERSONAL aquí
        UltimoFolio AS (
            SELECT * FROM (
                SELECT sfd.codPersonal, sfd.codFolio, sfd.fecha_emision, sfd.fecha_caducidad, 
                ROW_NUMBER() OVER (PARTITION BY sfd.codPersonal, sfd.codFolio ORDER BY sfd.codigo DESC) AS rn
                FROM sw_folios_detalles sfd
                WHERE sfd.habilitado = 1
            ) t WHERE rn = 1
        ),
```
Y el cruce en `FoliosConFechas`:
```sql
            -- Cruce seguro convirtiendo a INT para ignorar ceros a la izquierda y espacios
            LEFT JOIN UltimoFolio uf ON CAST(uf.codPersonal AS INT) = CAST(df.CODI_PERS AS INT) 
                                     AND uf.codFolio = fh.codFolio
```

4. **Agente `test-engineer`**: Ejecutará consultas de validación para garantizar que las fechas de la tabla `sw_folios_detalles` (como las de `codPersonal` = `00023`, `13693`, etc.) se reflejen correctamente en la vista.

## Verificación
Se ejecutarán scripts de pruebas automáticos, llamando al SP modificado antes de insertarlo en la base de datos para asegurar que devuelve los datos correctos sin errores de timeout.
