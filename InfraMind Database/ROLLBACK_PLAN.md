# InfraMind SQL Server Rollback Plan

## Goals
- Preserve data before schema changes.
- Enable quick rollback if deployment issues occur.

## Backup (recommended before every publish)
1. Ensure SQL Server is running.
2. Run a full backup:

```
sqlcmd -S localhost -E -Q "BACKUP DATABASE [InfraMind] TO DISK='C:\\backups\\InfraMind_full.bak' WITH INIT, STATS=5"
```

## Restore (rollback)
1. Ensure no active connections to the database.
2. Restore from backup:

```
sqlcmd -S localhost -E -Q "ALTER DATABASE [InfraMind] SET SINGLE_USER WITH ROLLBACK IMMEDIATE; RESTORE DATABASE [InfraMind] FROM DISK='C:\\backups\\InfraMind_full.bak' WITH REPLACE; ALTER DATABASE [InfraMind] SET MULTI_USER;"
```

## DACPAC Export (optional)
Export the current schema to a DACPAC for comparison:

```
sqlpackage /Action:Extract /TargetFile:"C:\\backups\\InfraMind_current.dacpac" /SourceServerName:localhost /SourceDatabaseName:InfraMind /TargetTrustServerCertificate:True
```

## Pre-deployment Safety
- Add data-preserving steps to [InfraMind Database/PreDeployment.sql](InfraMind%20Database/PreDeployment.sql).
- Keep steps idempotent and avoid destructive actions unless reviewed.
