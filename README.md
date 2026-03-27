# 🎓 Crestfield University Management System

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-blue.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-Educational-green.svg)]()

A comprehensive web-based university management system with role-based access for administrators, staff, and students. The system manages programmes, modules, staff accounts, student enrollment, grades, attendance, and a staff change request workflow.

---

## Table of Contents

- [Project Overview](#-project-overview)
- [System Requirements](#-system-requirements)
- [Installation Guide](#-installation-guide)
- [Login Credentials](#-login-credentials)
- [User Guides](#-user-guides)
  - [Administrator Guide](#-administrator-guide)
  - [Staff Guide](#-staff-guide)
  - [Student Guide](#-student-guide)
  - [Public User Guide](#-public-user-guide)
- [Security Features](#-security-features)
- [File Structure](#-file-structure)
- [Troubleshooting](#-troubleshooting)
- [Credits](#-credits)

---

## Project Overview

Crestfield University Management System is a PHP/MySQL web application that simulates a complete university management portal. It supports four user types — administrators, staff, students, and public visitors — each with their own portal and access level.

### Key Features

| Feature | Description |
|---------|-------------|
| **Programme Management** | Create, edit, and delete undergraduate and postgraduate programmes |
| **Module Management** | Manage modules, assign module leaders, and link modules to programmes |
| **Staff Management** | Add staff members, upload photos, create login accounts |
| **Student Management** | Create student accounts, enroll in programmes, track grades and attendance |
| **Change Request System** | Staff submit profile/module edit requests; admin approves or rejects |
| **Interest Registration** | Public visitors register interest in programmes |
| **CSV Export** | Export student accounts and interested students as CSV files |
| **Security** | CSRF protection, XSS prevention, SQL injection prevention, rate limiting |
| **Profile Photos** | Staff and student photos uploaded and displayed throughout the system |

### User Roles

| Role | Portal | Capabilities |
|------|--------|--------------|
| **Administrator** | `/admin/` | Full control — manage programmes, modules, staff, students, enrollments, grades, attendance |
| **Staff** | `/staff/` | View own modules/programmes, submit profile/module change requests |
| **Student** | `/student/` | View enrolled programme, modules, grades, attendance, update profile |
| **Public** | Root pages | Browse programmes/modules, view staff directory, register interest |

---

## System Requirements

### Software Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| **Operating System** | Windows 7 / macOS 10.12 / Linux | Windows 10/11 / macOS 13+ |
| **Web Server** | Apache 2.4 | Apache 2.4+ via XAMPP |
| **PHP** | 7.4 | 8.2 |
| **MySQL** | 5.7 | 8.0 |
| **Browser** | Chrome 90+ / Firefox 90+ / Safari 14+ | Latest version |

### Required PHP Extensions

| Extension | Purpose |
|-----------|---------|
| `mysqli` | Database connection and queries |
| `session` | User session management |
| `gd` | Image processing for uploaded photos |
| `mbstring` | Multi-byte string handling |

### Recommended Development Tools

| Tool | Purpose |
|------|---------|
| **XAMPP** | Local development environment (Apache + MySQL + PHP) |
| **phpMyAdmin** | Database management GUI |
| **VS Code / Sublime Text** | Code editor |

---

## Installation Guide

### Step 1: Install XAMPP

**Windows:**
1. Download XAMPP from: https://www.apachefriends.org/download.html
2. Run the installer — select **Apache**, **MySQL**, **PHP**, **phpMyAdmin**
3. Recommended install path: `C:\xampp\`

**macOS:**
1. Download XAMPP for macOS
2. Open the `.dmg` and drag XAMPP to your Applications folder

**Linux:**
```bash
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-mysqli php-gd php-mbstring
Step 2: Start XAMPP Services
Open XAMPP Control Panel

Click Start next to Apache (turns green)

Click Start next to MySQL (turns green)

Port conflict? If Apache fails to start, another program is using port 80.
Go to Config → Apache (httpd.conf) → change Listen 80 to Listen 8080
Then access the site at http://localhost:8080/University/

Step 3: Copy Project Files
Extract University.zip into your XAMPP htdocs folder:

OS	Path
Windows	C:\xampp\htdocs\
macOS	/Applications/XAMPP/htdocs/
Linux	/var/www/html/
Your final folder should be:

text
C:\xampp\htdocs\University\
Step 4: Set Folder Permissions
Windows:

Right-click uploads/ folder → Properties → Security → give Full Control to your user account

Repeat for logs/ folder

macOS / Linux:

bash
chmod -R 755 University/uploads/
chmod -R 755 University/logs/
Step 5: Run the Setup
Open your browser and go to:

text
http://localhost/University/setup.php
Follow the 3-step setup wizard:

Step 1: Verify database settings

Step 2: Create database tables (auto-created)

Step 3: Add demo data and create folders

Important: After setup completes, delete setup.php for security.

Login Credentials
Administrator
Field	Value
Login page	http://localhost/University/admin/login.php
Username	admin
Password	admin123
Staff
Login page: http://localhost/University/staff/login.php
Default password for all staff: Staff@1234

Username	Staff Member
alice.johnson	Dr. Alice Johnson
brian.lee	Dr. Brian Lee
carol.white	Prof. Carol White
david.green	Dr. David Green
emma.scott	Prof. Emma Scott
Students
Login page: http://localhost/University/student/login.php

Email	Password	Student Name
john.smith@student.com	password	John Smith
sarah.johnson@student.com	password	Sarah Johnson
michael.brown@student.com	password	Michael Brown
 User Guides
 Administrator Guide
Logging In
Go to http://localhost/University/admin/login.php

Enter admin / admin123 → click Sign In

Dashboard
View live counts for Programmes, Modules, Staff, and Registrations

Quick Actions for creating new Programmes, Modules, Staff

Recent registrations and popular programmes

Managing Programmes
Action	Steps
Create	Sidebar → Programmes → + New Programme → fill details → Create
Edit	Programmes list → Edit → update → Save Changes
Assign modules	Edit programme → Add Module → select module + year → Assign →
Remove module	Edit programme → click Remove next to assigned module
Delete	Programmes list → Delete → confirm
Managing Modules
Action	Steps
Create	Sidebar → Modules → + New Module → fill details → Create
Edit	Modules list → Edit → update → Save Changes
Delete	Modules list → Delete → confirm
Managing Staff
Action	Steps
Add staff	Sidebar → Staff → + Add Staff Member → fill Name, Bio, Photo → Add Member
Edit staff	Staff list → Edit → update → Save
Delete staff	Staff list → Delete → confirm
Managing Staff Accounts
Action	Steps
Create account	Staff Accounts → select staff → enter Username + Password → Create Account
Reset password	Staff Accounts → find account → Reset PW → enter new password
Delete account	Staff Accounts → Delete → confirm
Managing Students
Action	Steps
Create account	Student Accounts → fill details → Create Account
Enroll in programme	Student Enrollment → Enroll Student → select student + programme
Enter grades	Student Enrollment → Enter Grades → select student + module + grade
Mark attendance	Student Enrollment → Mark Attendance → select student + module + status
Delete account	Student Accounts → Delete → confirm
Export CSV	Click ⬇ Export CSV on any student page
Managing Interested Students
Action	Steps
View list	Sidebar → Interested Students → filter by programme
Delete record	Click Delete → confirm
Export CSV	Click Export CSV — downloads full mailing list
Reviewing Change Requests
Sidebar → Change Requests

Use tabs: Pending / Approved / Rejected / All

Add admin note (optional)

Click Approve (applies changes) or Reject

 Staff Guide
Logging In
Go to http://localhost/University/staff/login.php

Enter username (e.g., alice.johnson) and password Staff@1234

Dashboard
View modules and programmes you lead

Track pending/approved change requests

My Modules
View all modules you lead

Click Request Changes to submit module change requests

My Programmes
View all programmes you lead

Submitting Change Requests
Sidebar → My Requests → Profile Update or Module Change tab

Fill in new information

Click Submit Request →

Admin will review and approve/reject

Tracking Requests
Sidebar → My Requests → All Requests tab

Each request shows type, date, status, and admin note

 Student Guide
Logging In
Go to http://localhost/University/student/login.php

Enter email and password → click Login

Dashboard
View enrolled programme

View modules with lecturer information

Check grades and GPA

View attendance summary

View Modules
Scroll to My Modules section

Modules grouped by year

Click lecturer name to view their profile

View Grades
Scroll to My Grades section

Table shows module, grade, credits, academic year

GPA calculated automatically

View Attendance
Scroll to Recent Attendance section

Summary shows Present/Late/Absent/Excused counts

Attendance history table

Update Profile
Sidebar → My Profile

Update phone, address, date of birth

Click Update Profile →

Upload profile photo under "Profile Photo" section

 Public User Guide
Browse Programmes
Go to http://localhost/University/programmes.php

Filter by level or search

Click programme card for details

Browse Modules
Go to http://localhost/University/modules.php

Filter by module leader or search

View Staff Directory
Go to http://localhost/University/staff_directory.php

Search staff by name

Click staff card to view profile

Register Interest
Open any programme detail page

Click Register Interest →

Fill in name and email

Click Submit Registration →

View Registered Interests
Go to http://localhost/University/my-interests.php

Enter email address

Click View

Click Remove to withdraw interest

Security Features
Feature	Implementation
CSRF Protection	Tokens on all forms, verified on POST requests
XSS Prevention	All output uses htmlspecialchars()
SQL Injection Prevention	All queries use prepared statements
Rate Limiting	5 login attempts per 5 minutes
Account Locking	Locked after 5 failed attempts (15 minutes)
Password Hashing	bcrypt with cost factor 12
Session Security	HttpOnly cookies, SameSite=Strict, 30-minute timeout
Security Headers	X-XSS-Protection, X-Content-Type-Options, X-Frame-Options
File Upload Security	MIME validation, secure filenames, size limits
Security Logging	All important events logged to logs/security.log
 File Structure
text
University/
├── admin/                      # Admin portal
│   ├── index.php              # Dashboard
│   ├── login.php              # Admin login
│   ├── logout.php             # Admin logout
│   ├── programmes.php         # Programme management
│   ├── modules.php            # Module management
│   ├── staff.php              # Staff management
│   ├── staff_accounts.php     # Staff login accounts
│   ├── student_accounts.php   # Student accounts
│   ├── students.php           # Interested students
│   ├── student_enrollment.php # Enrollment, grades, attendance
│   ├── requests.php           # Change requests
│   └── layout.php             # Admin layout
├── staff/                      # Staff portal
│   ├── dashboard.php          # Staff dashboard
│   ├── login.php              # Staff login
│   ├── logout.php             # Staff logout
│   ├── my_modules.php         # Staff modules
│   ├── my_programmes.php      # Staff programmes
│   ├── requests.php           # Change requests
│   └── layout.php             # Staff layout
├── student/                    # Student portal
│   ├── dashboard.php          # Student dashboard
│   ├── login.php              # Student login
│   ├── logout.php             # Student logout
│   ├── profile.php            # Student profile
│   ├── grades.php             # View grades
│   ├── attendance.php         # View attendance
│   └── layout.php             # Student layout
├── includes/                   # Core includes
│   ├── auth.php               # Authentication & security
│   ├── db.php                 # Database connection
│   ├── header.php             # Public header
│   └── footer.php             # Public footer
├── uploads/                    # Uploaded files
│   ├── staff_photos/          # Staff profile photos
│   ├── student_photos/        # Student profile photos
│   └── programme_images/      # Programme images
├── logs/                       # Security logs
│   └── security.log           # All security events
├── css/
│   └── style.css              # Main stylesheet
├── database/
│   └── student_course_hub.sql # Database export
├── .htaccess                   # Apache security
├── index.php                   # Homepage
├── programmes.php              # Programme listing
├── programme_detail.php        # Programme details
├── modules.php                 # Module listing
├── module_detail.php           # Module details
├── staff_directory.php         # Staff directory
├── staff_portal.php            # Staff portal public view
├── staff_profile.php           # Staff profile public view
├── register_interest.php       # Interest registration
├── my-interests.php            # View registered interests
├── setup.php                   # One-time installer
└── README.md                   # This file
Troubleshooting
Problem	Solution
"Connection failed" error	Start MySQL in XAMPP Control Panel
CSS not loading	Hard refresh: Ctrl+Shift+R (Windows) / Cmd+Shift+R (macOS)
Staff can't log in	Run setup.php first to create demo data
Table doesn't exist error	Run setup.php again to import database
Staff photos not showing	Check photos in uploads/staff_photos/ match database filenames
File upload fails	Check uploads/ folder has write permissions
Blank/white page	Add error_reporting(E_ALL); ini_set('display_errors', 1); at top of file
"Cannot modify header information"	Check for whitespace before <?php tags
Login locked after 5 attempts	Wait 15 minutes or clear browser session
"Forbidden" error	Check .htaccess file or temporarily rename it
Student dashboard empty	Ensure student is enrolled in a programme via admin
404 error on pages	Make sure BASE_URL in includes/db.php is correct
 Credits
PHP — Server-side scripting language

MySQL — Relational database

Google Fonts — Playfair Display & DM Sans typography

XAMPP — Local development environment

Font Awesome — Icons (via CDN)

 License
This project is developed for educational purposes. All rights reserved.
And all images used are from the trusted and free to use (no copyright) sources.

