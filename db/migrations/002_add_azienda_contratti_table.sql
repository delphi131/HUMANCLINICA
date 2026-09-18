-- Stores collaboration contracts per company: either generated from a
-- template (content held inline as HTML) or an uploaded file (stored on
-- disk under storage/contracts/, path recorded here).
--
-- Adjust the FK target (dbo.tAzienda / id_Azienda) first if
-- config/schema.php's "azienda" mapping needed correcting after running
-- php tools/list_columns.php tAzienda.

IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'tAziendaContratti')
BEGIN
    CREATE TABLE dbo.tAziendaContratti (
        id INT IDENTITY(1,1) PRIMARY KEY,
        id_Azienda INT NOT NULL,
        source VARCHAR(20) NOT NULL, -- 'generated' | 'uploaded'
        content NVARCHAR(MAX) NULL,  -- generated HTML, NULL if uploaded
        file_name NVARCHAR(255) NULL, -- original uploaded filename, NULL if generated
        file_path NVARCHAR(500) NULL, -- relative path under storage/contracts/, NULL if generated
        created_at DATETIME NOT NULL DEFAULT GETDATE(),
        created_by NVARCHAR(100) NULL,
        CONSTRAINT FK_tAziendaContratti_tAzienda FOREIGN KEY (id_Azienda) REFERENCES dbo.tAzienda(id_Azienda)
    );
END
GO
