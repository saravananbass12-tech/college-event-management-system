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
