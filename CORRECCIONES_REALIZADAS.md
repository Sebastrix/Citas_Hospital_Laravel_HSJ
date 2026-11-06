# Correcciones realizadas

- Se retiró la dependencia de MySQL/MariaDB.
- CITAS fue configurada como Microsoft SQL Server local mediante `sqlsrv`.
- SIGH permanece como Microsoft SQL Server remoto mediante la conexión `sigh`.
- Se reemplazaron consultas de diagnóstico MySQL (`DATABASE()`, `@@hostname`, `NOW()`) por SQL Server (`DB_NAME()`, `@@SERVERNAME`, `GETDATE()`).
- Los modelos de sala de espera y chatbot usan la conexión `sqlsrv`.
- La migración de CITAS usa SQL Server y conserva tablas existentes.
- Si existe una columna antigua `ExpiraEn`, se crea `FechaExpiracion` y se copian sus valores sin borrar datos.
- No se requiere `pdo_mysql`.
