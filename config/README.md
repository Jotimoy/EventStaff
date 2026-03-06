# EventStaff Database Setup Guide

## 📋 Database Information

- **Database Name:** `eventstaff_db`
- **Host:** `localhost`
- **Username:** `root`
- **Password:** _(empty for XAMPP)_

---

## 🚀 How to Setup Database

### Method 1: Using phpMyAdmin (Easiest)

1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL**

2. **Open phpMyAdmin**
   - Go to: http://localhost/phpmyadmin
   - Or click "Admin" button next to MySQL in XAMPP

3. **Import SQL File**
   - Click on **"Import"** tab at the top
   - Click **"Choose File"** button
   - Select: `config/setup.sql`
   - Click **"Go"** button at the bottom
   - Wait for success message

4. **Verify Setup**
   - Click on `eventstaff_db` database in left sidebar
   - You should see 7 tables:
     - users
     - employee_profiles
     - organizer_profiles
     - events
     - event_shifts
     - shift_applications
     - payments

---

### Method 2: Using MySQL Command Line

```bash
# Navigate to your project folder
cd /Applications/XAMPP/xamppfiles/htdocs/EventStaff

# Run the SQL file
/Applications/XAMPP/xamppfiles/bin/mysql -u root < config/setup.sql
```

---

## 👤 Default Login Credentials

After running the setup script, you can login with these test accounts:

### Admin Account
- **Email:** admin@eventstaff.com
- **Password:** admin123

### Organizer Account (Test)
- **Email:** organizer@test.com
- **Password:** password123

### Employee Account (Test)
- **Email:** employee@test.com
- **Password:** password123

---

## 🗄️ Database Structure

### Table: `users`
Main authentication table
- id, email, password (MD5), role (admin/organizer/employee), created_at

### Table: `employee_profiles`
Worker profile information
- id, user_id (FK), full_name, phone, skills, experience, created_at

### Table: `organizer_profiles`
Company profile information
- id, user_id (FK), company_name, contact_person, phone, address, created_at

### Table: `events`
Event listings by organizers
- id, organizer_id (FK), title, description, location, event_date, created_at

### Table: `event_shifts`
Shifts/jobs within events
- id, event_id (FK), shift_date, start_time, end_time, required_staff, payment_per_shift, status, created_at

### Table: `shift_applications`
Employee applications for shifts
- id, shift_id (FK), employee_id (FK), application_status (pending/approved/rejected), applied_at, reviewed_at

### Table: `payments`
Payment tracking for approved work
- id, application_id (FK), amount, payment_status (pending/paid), paid_at, created_at

---

## ✅ Test Your Database Connection

After setup, visit:
- http://localhost/EventStaff/config/test_connection.php

You should see a success message with green background.

---

## 🔧 Troubleshooting

### Error: "Access denied for user 'root'"
- Make sure MySQL is running in XAMPP
- Check if password is empty (XAMPP default)
- Try restarting MySQL in XAMPP

### Error: "Unknown database 'eventstaff_db'"
- The database wasn't created
- Re-run the setup.sql file
- Check phpMyAdmin if database exists

### Error: "Table already exists"
- Database is already set up
- You can drop the database and re-import:
  ```sql
  DROP DATABASE eventstaff_db;
  ```
  Then re-run setup.sql

---

## 📝 Next Steps

After database setup is complete:
1. ✅ Test database connection
2. Create authentication pages (register/login)
3. Build user dashboards
4. Implement features

---

**Need help?** Check the main README.md or contact your instructor.
