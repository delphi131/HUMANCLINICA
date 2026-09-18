-- Standalone calendar reminders ("promemoria"), separate from tReservations:
-- these are staff notes on the calendar (not real customer bookings), so they
-- live in their own table instead of polluting the ASP.NET app's data.
-- "day" reuses the same int YYYYMMDD convention as tReservations.day.

IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'tPHPReminders')
BEGIN
    CREATE TABLE dbo.tPHPReminders (
        id INT IDENTITY(1,1) PRIMARY KEY,
        title NVARCHAR(200) NOT NULL,
        note NVARCHAR(MAX) NULL,
        day INT NOT NULL,
        email NVARCHAR(200) NULL,
        send_email BIT NOT NULL DEFAULT 0,
        sent_at DATETIME NULL,
        created_by NVARCHAR(100) NULL,
        created_at DATETIME NOT NULL DEFAULT GETDATE()
    );
END
GO
