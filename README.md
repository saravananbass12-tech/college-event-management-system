# 🎓 College Event Management System — 2026

<p align="center">

## 🚀 Smart • Digital • QR-Based Event Management

A modern web-based platform for managing **college events, student registrations, event participation, and QR-code attendance** through a centralized digital system.

</p>

<p align="center">

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)

</p>

---

# 📌 About the Project

**College Event Management System** is a web-based application developed to digitize and simplify the management of college events.

The system provides a centralized platform where:

- 👨‍💼 Administrators can manage events and students
- 👨‍🎓 Students can create accounts and register for events
- 🎟️ Students can participate in available events
- 📱 QR codes can be used for event attendance
- 📊 Administrators can monitor attendance and participation
- 📁 Student documents can be uploaded and managed
- 🗄️ Data can be securely stored using MySQL

The main goal is to replace **manual registration, paper-based attendance, and scattered event records** with an organized digital solution.

---

# ✨ Key Features

| Feature | Description |
|---|---|
| 🔐 Authentication | Secure Admin and Student login |
| 📅 Event Management | Create, update, view and delete events |
| 👨‍🎓 Student Registration | Online student account registration |
| 🎟️ Event Registration | Register for available college events |
| 📱 QR Attendance | QR-code based attendance system |
| 👥 Student Management | View and manage registered students |
| 📊 Attendance Management | Track event participation |
| 📁 Document Upload | Upload and manage student documents |
| 🗄️ MySQL Database | Centralized database management |
| 📱 Responsive UI | Desktop and mobile friendly |

---

# 🏗️ System Architecture

```text
                    🎓 COLLEGE EVENT MANAGEMENT SYSTEM
                                  │
                ┌─────────────────┴─────────────────┐
                │                                   │
             👨‍💼 ADMIN                           👨‍🎓 STUDENT
                │                                   │
       ┌────────┼─────────┐                 ┌───────┼────────┐
       │        │         │                 │       │        │
    Events   Students  Attendance        Register  Events  QR Code
       │        │         │                 │       │        │
       └────────┴─────────┴────────┬────────┴───────┴────────┘
                                   │
                            🗄️ MySQL Database
```

---

# 🛠️ Technology Stack

## 🎨 Frontend

- HTML5
- CSS3
- JavaScript ES6
- Bootstrap 5

## ⚙️ Backend

- PHP 8.x

## 🗄️ Database

- MySQL 8.x

## 🔧 Development Tools

- XAMPP
- phpMyAdmin
- Visual Studio Code

---

# 📂 Project Structure

```text
college-event-management-system/
│
├── admin/
│   ├── dashboard.php
│   ├── events.php
│   ├── students.php
│   └── attendance.php
│
├── student/
│   ├── dashboard.php
│   ├── events.php
│   ├── registration.php
│   └── qr_code.php
│
├── includes/
│   ├── db.php
│   ├── header.php
│   └── footer.php
│
├── uploads/
│   └── students/
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── screenshots/
│   ├── admin.png
│   ├── events.png
│   ├── file.png
│   ├── login.png
│   ├── register.png
│   └── report.png
│
├── database/
│   └── college_event_management.sql
│
├── index.php
├── login.php
├── register.php
├── logout.php
├── .gitignore
└── README.md
```

---

# ⚙️ Installation & Setup

## 1️⃣ Install XAMPP

Install **XAMPP** on your computer.

Start the following services:

```text
Apache
MySQL
```

---

## 2️⃣ Clone the Repository

```bash
git clone https://github.com/saravananbass12-tech/college-event-management-system.git
```

---

## 3️⃣ Move Project to XAMPP

Copy the project folder into:

```text
C:\xampp\htdocs\
```

The final location should be:

```text
C:\xampp\htdocs\college-event-management-system\
```

---

## 4️⃣ Create MySQL Database

Open:

```text
http://localhost/phpmyadmin/
```

Create a database:

```text
college_event_management
```

Then import:

```text
database/college_event_management.sql
```

---

## 5️⃣ Configure Database Connection

Open:

```text
includes/db.php
```

Use your MySQL database configuration:

```php
<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "college_event_management";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>
```

---

## 6️⃣ Start the Application

Open your browser:

```text
http://localhost/college-event-management-system/
```

---

# 🔄 Application Workflow

```text
                 👨‍🎓 STUDENT
                     │
                     ▼
               Register / Login
                     │
                     ▼
                View Events
                     │
                     ▼
              Register for Event
                     │
                     ▼
               Generate QR Code
                     │
                     ▼
                Attend Event
                     │
                     ▼
                  QR Scan
                     │
                     ▼
             Attendance Recorded
                     │
                     ▼
                  🗄️ MySQL
```

---

# 👨‍💼 Admin Workflow

```text
Admin Login
     │
     ▼
Admin Dashboard
     │
 ┌───┼───────────────┐
 ▼   ▼               ▼
Events Students   Attendance
 │     │               │
 ▼     ▼               ▼
Manage  Manage       Track
Events  Students     Participation
```

---

# 📱 QR Attendance System

The QR-based attendance module provides a faster alternative to manual attendance.

## Process

```text
Student Registers
       ↓
Event Registration
       ↓
QR Code Generated
       ↓
Student Attends Event
       ↓
QR Code Scanned
       ↓
Student Verified
       ↓
Attendance Stored
```

## Benefits

- ⚡ Faster attendance
- 📱 Mobile-friendly scanning
- ❌ Less manual work
- 📊 Better attendance tracking
- 🗄️ Centralized records

---

# 🎯 Project Objectives

- ✅ Digitize college event management
- ✅ Reduce manual registration
- ✅ Simplify student participation
- ✅ Improve attendance tracking
- ✅ Reduce paperwork
- ✅ Centralize event information
- ✅ Implement QR-based attendance
- ✅ Improve administrative efficiency
- ✅ Provide a responsive web experience

---

# 📊 Main Modules

## 🔐 Authentication Module

- Admin login
- Student registration
- Student login
- Logout
- Session management

## 📅 Event Module

- Add event
- Edit event
- Delete event
- View events
- Event details
- Event registration

## 👨‍🎓 Student Module

- Student registration
- Student profile
- Event registration
- Document upload
- QR code generation

## 📱 Attendance Module

- QR generation
- QR scanning
- Attendance verification
- Attendance records
- Participation tracking

## 👨‍💼 Admin Module

- Admin dashboard
- Student management
- Event management
- Attendance management
- Reports

---

# 📸 Screenshots

The following screenshots demonstrate the main modules and user interfaces of the **College Event Management System**.

---

## 🔐 Login Page

<p align="center">
  <img src="screenshots/login.png" alt="Login Page" width="850">
</p>

---

## 📝 Student Registration

<p align="center">
  <img src="screenshots/register.png" alt="Student Registration Page" width="850">
</p>

---

## 👨‍💼 Admin Dashboard

<p align="center">
  <img src="screenshots/admin.png" alt="Admin Dashboard" width="850">
</p>

---

## 📅 Event Management

<p align="center">
  <img src="screenshots/events.png" alt="Event Management Page" width="850">
</p>

---

## 📁 File / Document Management

<p align="center">
  <img src="screenshots/file.png" alt="File Management Page" width="850">
</p>

---

## 📊 Reports & Attendance

<p align="center">
  <img src="screenshots/report.png" alt="Reports and Attendance Page" width="850">
</p>

---

# 🖥️ Screenshots Overview

<p align="center">

<img src="screenshots/login.png" width="45%" alt="Login Page">

<img src="screenshots/register.png" width="45%" alt="Registration Page">

<br><br>

<img src="screenshots/admin.png" width="45%" alt="Admin Dashboard">

<img src="screenshots/events.png" width="45%" alt="Event Management">

<br><br>

<img src="screenshots/file.png" width="45%" alt="File Management">

<img src="screenshots/report.png" width="45%" alt="Reports">

</p>

---

# 🧪 Testing

The application can be tested for the following functions:

| Test Case | Description |
|---|---|
| 🔐 Login | Verify user authentication |
| 📝 Registration | Verify student registration |
| 📅 Event Creation | Verify admin event creation |
| ✏️ Event Update | Verify event editing |
| 🗑️ Event Delete | Verify event deletion |
| 🎟️ Event Registration | Verify student event registration |
| 📱 QR Generation | Verify QR code generation |
| 📷 QR Scanning | Verify QR attendance scanning |
| 📊 Attendance | Verify attendance recording |
| 📁 File Upload | Verify document upload |
| 🗄️ Database | Verify MySQL operations |
| 👨‍💼 Admin | Verify administrator functions |

---

# 🔒 Security Considerations

The system should include:

- 🔐 Password hashing
- 🔑 Session-based authentication
- 🛡️ Input validation
- 🗄️ Prepared SQL statements
- 🚫 SQL injection prevention
- 📁 File upload validation
- 👥 Role-based access control
- 🔒 Secure database configuration
- 🧹 Input sanitization
- 🚪 Unauthorized access prevention

---

# 🚀 Advantages

## 👨‍🎓 For Students

- 📱 Easy online registration
- 🎟️ Simple event participation
- 📲 QR-based attendance
- 📁 Digital document submission
- ⚡ Faster event check-in

## 👨‍💼 For Administrators

- 👨‍💼 Centralized management
- 📅 Easy event creation
- 👥 Student management
- 📊 Attendance monitoring
- 📈 Participation reports
- 🗄️ Centralized database

---

# 🔮 Future Enhancements

Future versions of the system can include:

- 📧 Email notifications
- 📱 Android / iOS mobile application
- 🔔 Real-time event notifications
- 📊 Advanced admin analytics
- 📈 Event participation reports
- ☁️ Cloud deployment
- 🔐 Two-factor authentication
- 🤖 AI-based event recommendations
- 🎓 Digital certificate generation
- 📲 WhatsApp notifications
- 📊 Advanced event analytics
- 🔔 Push notifications
- 📅 Calendar integration

---

# 📚 Project Information

| Category | Details |
|---|---|
| 🎓 Project Type | Academic / Educational |
| 📅 Year | 2026 |
| 💻 Application | Web Application |
| ⚙️ Backend | PHP 8.x |
| 🗄️ Database | MySQL 8.x |
| 🎨 Frontend | HTML5, CSS3, JavaScript, Bootstrap 5 |
| 🔧 Environment | XAMPP |
| 🛠️ Database Tool | phpMyAdmin |
| 📱 Attendance | QR Code |
| 🔐 Authentication | Admin & Student |
| 📊 Reporting | Attendance & Participation |

---

# 👨‍💻 Developer

## SARAVANAN D

**BCA Graduate | Web Developer**

### 💻 Technical Skills

```text
HTML • CSS • JavaScript • Bootstrap
PHP • MySQL • Python
Power BI • Data Analytics
```

### 💡 Areas of Interest

- Web Application Development
- Database Management
- PHP & MySQL Development
- Python Development
- Data Analytics
- Power BI
- Artificial Intelligence
- Emerging Technologies

---

# 📌 GitHub Repository

```text
https://github.com/saravananbass12-tech/college-event-management-system
```

---

# 📄 License

This project is developed for **educational and academic purposes**.

---

<p align="center">

## 🎓 College Event Management System — 2026

### 🚀 Smart • Digital • QR-Based Event Management

⭐ **If you find this project useful, consider giving it a star!**

</p>
