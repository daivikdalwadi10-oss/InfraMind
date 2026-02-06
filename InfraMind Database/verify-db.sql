-- Smoke checks for InfraMind database
-- Run with: sqlcmd -S localhost -E -d InfraMind -i "InfraMind Database\verify-db.sql"

SET NOCOUNT ON;

PRINT 'Tables:';
SELECT name AS table_name
FROM sys.tables
ORDER BY name;

PRINT 'Foreign keys:';
SELECT fk.name AS foreign_key,
       tp.name AS parent_table,
       tr.name AS referenced_table
FROM sys.foreign_keys fk
JOIN sys.tables tp ON fk.parent_object_id = tp.object_id
JOIN sys.tables tr ON fk.referenced_object_id = tr.object_id
ORDER BY fk.name;

PRINT 'Row counts:';
SELECT t.name AS table_name,
       p.rows AS row_count
FROM sys.tables t
JOIN sys.partitions p ON t.object_id = p.object_id
WHERE p.index_id IN (0, 1)
ORDER BY t.name;

PRINT 'Check constraints:';
SELECT cc.name AS check_name,
       t.name AS table_name
FROM sys.check_constraints cc
JOIN sys.tables t ON cc.parent_object_id = t.object_id
ORDER BY t.name, cc.name;
