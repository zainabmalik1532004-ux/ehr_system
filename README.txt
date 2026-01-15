========================================
EHR SYSTEM - INSTALLATION INSTRUCTIONS
========================================

REQUIREMENTS:
- XAMPP (PHP 7.4+ and MySQL)
- Web browser (Chrome, Firefox, Safari)

INSTALLATION STEPS:

1. Extract this ZIP file to your XAMPP htdocs folder
   Location: /Applications/XAMPP/htdocs/ehr_system/

2. Start XAMPP:
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL

3. Create Database:
   - Open browser and go to http://localhost/phpmyadmin
   - Click "New" on the left sidebar
   - Create database named: ehr_system
   - Click "Import" tab
   - Choose file: ehr_system.sql (included in this ZIP)
   - Click "Go" button

4. Configure Database Connection:
   - Open file: db_connect.php
   - Verify these settings:
     $host = "localhost";
     $username = "root";
     $password = "";
     $dbname = "ehr_system";

5. Run the Application:
   - Open browser and go to: http://localhost/ehr_system
   - Login with the credentials below

DEFAULT LOGIN CREDENTIALS:
   Email: john@example.com
   Username: drjohn
   Password: Password123

FEATURES:
- Patient registration and management
- SOAP notes documentation
- Examination sheets (6 types)
- Anatomical diagram drawing
- Document upload/download/delete
- Search and filter patients

TROUBLESHOOTING:
- If "Access Denied" error: Check db_connect.php settings
- If files don't upload: Run in Terminal: chmod -R 777 uploads/
- If pages show blank: Check PHP error logs in XAMPP

SYSTEM DEVELOPED BY: Zainab Malik
COURSE: Information System of Healthcare
DATE: January 2026
========================================
