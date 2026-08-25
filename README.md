# 🎓 College Event Management System — 2026

<p align="center">

### 🚀 Smart • Digital • QR-Based Event Management

A modern web-based platform for managing **college events, student registrations, event participation, and QR-code attendance** through a centralized digital system.

</p>

<p align="center">

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge\&logo=php\&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=for-the-badge\&logo=mysql\&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge\&logo=html5\&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge\&logo=css3\&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=for-the-badge\&logo=javascript\&logoColor=black)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge\&logo=bootstrap\&logoColor=white)

</p>

---

## 📌 About the Project

**College Event Management System** is a web-based application developed to digitize and simplify the management of college events.

The system provides a centralized platform where:

* 👨‍💼 Administrators can manage events and students
* 👨‍🎓 Students can create accounts and register for events
* 🎟️ Students can participate in available events
* 📱 QR codes can be used for event attendance
* 📊 Administrators can monitor attendance and participation
* 🗄️ Data can be securely stored using MySQL

The main goal is to replace **manual registration, paper-based attendance, and scattered event records** with an organized digital solution.

---

## ✨ Key Features

| Feature                    | Description                               |
| -------------------------- | ----------------------------------------- |
| 🔐 Authentication          | Secure Admin and Student login            |
| 📅 Event Management        | Create, update, view and delete events    |
| 👨‍🎓 Student Registration | Online student account registration       |
| 🎟️ Event Registration     | Register for available college events     |
| 📱 QR Attendance           | QR-code based attendance system           |
| 👥 Student Management      | View and manage registered students       |
| 📊 Attendance Management   | Track event participation                 |
| 📁 Document Upload         | Upload and manage student documents       |
| 🗄️ MySQL Database         | Centralized database management           |
| 📱 Responsive UI           | Access the system from desktop and mobile |

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

### 🎨 Frontend

* HTML5
* CSS3
* JavaScript
* Bootstrap 5

### ⚙️ Backend

* PHP

### 🗄️ Database

* MySQL

### 🔧 Development Tools

* XAMPP
* phpMyAdmin

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

Start:

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

Create a database, for example:

```text
college_event_management
```

Import:

```text
database/college_event_management.sql
```

---

## 5️⃣ Configure Database Connection

Update the database configuration file according to your MySQL settings.

Example:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "college_event_management";
```

---

## 6️⃣ Start the Application

Open your browser and visit:

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

### Process

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

### Benefits

* ⚡ Faster attendance
* 📱 Mobile-friendly scanning
* ❌ Less manual work
* 📊 Better attendance tracking
* 🗄️ Centralized records

---

# 🎯 Project Objectives

* ✅ Digitize college event management
* ✅ Reduce manual registration
* ✅ Simplify student participation
* ✅ Improve attendance tracking
* ✅ Reduce paperwork
* ✅ Centralize event information
* ✅ Implement QR-based attendance
* ✅ Improve administrative efficiency
* ✅ Provide a responsive web experience

---

# 📊 Main Modules

### 🔐 Authentication Module

* Admin login
* Student registration
* Student login
* Logout

### 📅 Event Module

* Add event
* Edit event
* Delete event
* View events
* Event details

### 👨‍🎓 Student Module

* Student registration
* Student profile
* Event registration
* Document upload

### 📱 Attendance Module

* QR generation
* QR scanning
* Attendance verification
* Attendance records

### 👨‍💼 Admin Module

* Dashboard
* Student management
* Event management
* Attendance management

---

# 🔮 Future Enhancements

* 📧 Email notifications
* 📱 Android / iOS mobile application
* 🔔 Real-time event notifications
* 📊 Advanced admin analytics
* 📈 Event participation reports
* ☁️ Cloud deployment
* 🔐 Two-factor authentication
* 🤖 AI-based event recommendations
* 🎓 Digital certificate generation
* 📲 WhatsApp notifications
* 📊 Advanced event analytics

---

# 📸 Screenshots

Add your application screenshots here:

### 🔐 Login Page

```text
Add login screenshot here
```

### 🏠 Student Dashboard

```text
Add student dashboard screenshot here
```

### 📅 Event Registration

```text
Add event registration screenshot here
```

### 📱 QR Attendance

```text
Add QR attendance screenshot here
```

### 👨‍💼 Admin Dashboard

```text
Add admin dashboard screenshot here
```

---

# 🧪 Testing

The application can be tested for:

* Login validation
* Student registration
* Event creation
* Event registration
* QR generation
* QR scanning
* Attendance recording
* Document upload
* Database operations
* Admin functions

---

# 🔒 Security Considerations

The system should include:

* Password hashing
* Session-based authentication
* Input validation
* SQL injection prevention
* File upload validation
* Access control for Admin/Student modules
* Secure database configuration

---

# 📚 Project Information

| Category        | Details                          |
| --------------- | -------------------------------- |
| 🎓 Project Type | Academic / Educational           |
| 📅 Year         | 2026                             |
| 💻 Application  | Web Application                  |
| ⚙️ Backend      | PHP                              |
| 🗄️ Database    | MySQL                            |
| 🎨 Frontend     | HTML, CSS, JavaScript, Bootstrap |
| 🔧 Environment  | XAMPP                            |
| 📱 Attendance   | QR Code                          |

---

# 👨‍💻 Developer

## SARAVANAN

**BCA Student | Web Developer**

### 💻 Technical Skills

```text
HTML • CSS • JavaScript • Bootstrap
PHP • MySQL • Python
```

### 💡 Areas of Interest

* Web Application Development
* Database Management
* Python Development
* Data Analytics
* Power BI
* AI & Emerging Technologies

---

# 📄 License

This project is developed for **educational and academic purposes**.

---

<p align="center">

### ⭐ If you find this project useful, consider giving it a star!



</p>
