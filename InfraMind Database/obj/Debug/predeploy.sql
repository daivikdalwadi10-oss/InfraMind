/*
  Pre-deployment script for data-preserving changes.
  This runs before the DACPAC schema update.

  Add safe, idempotent migration steps here. Examples:
  - Create staging tables to preserve data before a type/shape change
  - Backfill new columns with derived values
  - Rename or copy data between tables

  IMPORTANT:
  - Keep operations idempotent (re-runnable).
  - Avoid destructive actions unless intentional and reviewed.
*/

-- Example: add a new column with a default only if missing
-- IF COL_LENGTH(N'dbo.MyTable', N'NewColumn') IS NULL
-- BEGIN
--     ALTER TABLE dbo.MyTable ADD NewColumn NVARCHAR(100) NULL;
--     UPDATE dbo.MyTable SET NewColumn = N'' WHERE NewColumn IS NULL;
-- END

-- Example: preserve data before a column type change
-- IF OBJECT_ID(N'dbo.MyTable_Backup', N'U') IS NULL
-- BEGIN
--     SELECT * INTO dbo.MyTable_Backup FROM dbo.MyTable;
-- END
GO
