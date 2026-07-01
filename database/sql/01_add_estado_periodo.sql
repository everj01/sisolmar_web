-- Script: 01_add_estado_periodo.sql
-- Propósito: Agregar el campo `estado_periodo` para controlar la validez de un curso clonado.
-- Ejecutar en: SQL Server Management Studio (SSMS) conectándose a la Base de Datos del proyecto.

-- Asegúrese de estar utilizando la base de datos correcta
-- USE [nombre_de_la_bd];
-- GO

-- 1. Agregamos la columna estado_periodo con valor por defecto 'VIGENTE' y que no acepte NULOS.
ALTER TABLE [dbo].[sw_cursos_programacion]
ADD [estado_periodo] VARCHAR(20) DEFAULT 'VIGENTE' NOT NULL;
GO

-- 2. Aseguramos que los registros existentes se establezcan en 'VIGENTE' (Opcional si es NOT NULL DEFAULT, pero buena práctica por si la versión antigua de SQL lo requiere explícito)
UPDATE [dbo].[sw_cursos_programacion]
SET [estado_periodo] = 'VIGENTE';
GO

PRINT 'Columna estado_periodo creada correctamente.';
