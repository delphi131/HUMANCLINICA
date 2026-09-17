-- Adds a column for the PHP admin panel's own bcrypt password, kept separate
-- from tUsers.Password (the reversible cipher used by the existing ASP.NET app).
-- The ASP.NET app is not touched: it keeps reading/writing the old column.
--
-- Run once against the HumanClinica database, adjusting the table/column
-- names first if config/schema.php was changed to match your real schema.

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.tUsers') AND name = 'PasswordPHP'
)
BEGIN
    ALTER TABLE dbo.tUsers ADD PasswordPHP NVARCHAR(255) NULL;
END
GO
