<?php
/*
╔══════════════════════════════════════════════════════════════════════════════╗
║                    🎓 COLLEGE EVENT MANAGEMENT SYSTEM 🎓                      ║
║                    with Certificates, Excel Reports & More                   ║
║                             by Thiru Project                                 ║
╚══════════════════════════════════════════════════════════════════════════════╝
*/

session_start();
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'thiru';



// 📁 Database Connection
try {
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) die("❌ Connection failed: " . $conn->connect_error);
    
    $conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
    $conn->select_db($dbname);
    
    // 📦 Create Tables with Certificates, ID Card field, and Class/Level
    $tables = [
        "CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            email VARCHAR(100),
            signature VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reg_no VARCHAR(20) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            department VARCHAR(50) NOT NULL,
            class_level VARCHAR(20) NOT NULL,
            year_of_study INT NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(15),
            photo VARCHAR(255),
            id_card_photo VARCHAR(255),
            password VARCHAR(255) NOT NULL,
            status ENUM('pending','active','blocked') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            event_date DATE NOT NULL,
            venue VARCHAR(100),
            max_participants INT DEFAULT 100,
            created_by INT,
            status ENUM('upcoming','ongoing','completed') DEFAULT 'upcoming',
            event_poster VARCHAR(255),
            event_schedule VARCHAR(255),
            event_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES admin(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            student_id INT NOT NULL,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending','approved','rejected') DEFAULT 'approved',
            attendance_status ENUM('present','absent','late','not_marked') DEFAULT 'not_marked',
            certificate_issued BOOLEAN DEFAULT FALSE,
            certificate_no VARCHAR(50) UNIQUE,
            certificate_issued_date DATE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_registration (event_id, student_id)
        )",
        
        "CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            student_id INT NOT NULL,
            marked_date DATE NOT NULL,
            marked_time TIME DEFAULT CURRENT_TIMESTAMP,
            status ENUM('present','absent','late') DEFAULT 'present',
            remarks VARCHAR(255),
            marked_by INT,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (marked_by) REFERENCES admin(id),
            UNIQUE KEY unique_attendance (event_id, student_id, marked_date)
        )",
        
        "CREATE TABLE IF NOT EXISTS certificates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registration_id INT NOT NULL,
            event_id INT NOT NULL,
            student_id INT NOT NULL,
            certificate_no VARCHAR(50) UNIQUE NOT NULL,
            issue_date DATE NOT NULL,
            file_path VARCHAR(255),
            verification_code VARCHAR(100) UNIQUE,
            issued_by INT,
            hod_signature VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (issued_by) REFERENCES admin(id)
        )"
    ];
    
    foreach ($tables as $sql) $conn->query($sql);
    
    // 👑 Default Admin - ADDED (admin / admin123)
    $checkAdmin = $conn->query("SELECT * FROM admin WHERE username = 'admin'");
    if ($checkAdmin->num_rows == 0) {
        $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO admin (username, password, full_name, email) 
                      VALUES ('thiru', '$defaultPass', 'Thiru Administrator', 'thiru@college.edu')");
    }
    
    // Create uploads directory for signatures and event materials
    $signature_dir = "uploads/signatures/";
    $event_dir = "uploads/events/";
    $excel_dir = "uploads/excel/";
    if (!file_exists($signature_dir)) {
        mkdir($signature_dir, 0777, true);
    }
    if (!file_exists($event_dir)) {
        mkdir($event_dir, 0777, true);
    }
    if (!file_exists($excel_dir)) {
        mkdir($excel_dir, 0777, true);
    }
    
} catch (Exception $e) {
    die("❌ Database Error: " . $e->getMessage());
}

// 🎯 Main Router
$page = $_GET['page'] ?? 'home';
$action = $_GET['action'] ?? '';

// 🚪 Logout
if ($action == 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// 🔐 Login Handler
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if ($role == 'admin') {
        $result = $conn->query("SELECT * FROM admin WHERE username = '$username'");
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['username'] = $admin['full_name'] ?? $admin['username'];
                $_SESSION['role'] = 'admin';
                header('Location: index.php?page=dashboard');
                exit;
            }
        }
        $error = "❌ Invalid admin credentials!";
    } else {
        $result = $conn->query("SELECT * FROM students WHERE (reg_no = '$username' OR email = '$username') AND status = 'active'");
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            if (password_verify($password, $student['password'])) {
                $_SESSION['user_id'] = $student['id'];
                $_SESSION['username'] = $student['name'];
                $_SESSION['role'] = 'student';
                $_SESSION['reg_no'] = $student['reg_no'];
                header('Location: index.php?page=student_dashboard');
                exit;
            }
        }
        $error = "❌ Invalid credentials or account not approved!";
    }
}

// 👑 Admin Signature Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_signature'])) {
    $admin_id = $_SESSION['user_id'];
    
    $target_dir = "uploads/signatures/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Handle signature upload
    $signature = '';
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] == 0) {
        $sig_ext = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
        $signature = $target_dir . 'signature_' . $admin_id . '_' . time() . '.' . $sig_ext;
        move_uploaded_file($_FILES['signature']['tmp_name'], $signature);
        
        $conn->query("UPDATE admin SET signature = '$signature' WHERE id = $admin_id");
        $success = "✅ Signature uploaded successfully!";
    }
}

// Handle HOD Signature Upload (Only HOD sign remains)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_hod_signature'])) {
    $target_dir = "uploads/signatures/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Handle HOD signature upload
    $hod_signature = '';
    if (isset($_FILES['hod_signature']) && $_FILES['hod_signature']['error'] == 0) {
        $hod_ext = pathinfo($_FILES['hod_signature']['name'], PATHINFO_EXTENSION);
        $hod_signature = $target_dir . 'hod_signature_' . time() . '.' . $hod_ext;
        move_uploaded_file($_FILES['hod_signature']['tmp_name'], $hod_signature);
        $_SESSION['hod_signature'] = $hod_signature;
        $success = "✅ HOD Signature uploaded successfully!";
    }
}

// 📤 Excel File Upload Handler
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_excel_file'])) {
    $target_dir = "uploads/excel/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $excel_file = '';
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
        $excel_ext = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
        $excel_file = $target_dir . 'excel_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $_FILES['excel_file']['name']) . '.' . $excel_ext;
        move_uploaded_file($_FILES['excel_file']['tmp_name'], $excel_file);
        
        // Store in session or database as needed
        $_SESSION['uploaded_excel'] = $excel_file;
        $success = "✅ Excel file uploaded successfully!";
    }
}

// 📝 Student Registration Handler with Enhanced Validation and Class/Level (Updated Format: 2 digits + 3 letters + 6 digits)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_student'])) {
    $reg = $conn->real_escape_string($_POST['reg_no']);
    $name = $conn->real_escape_string($_POST['name']);
    $dept = $conn->real_escape_string($_POST['department']);
    $class_level = $conn->real_escape_string($_POST['class_level']);
    $year_of_study = intval($_POST['year_of_study']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Validate register number format (2 digits + 3 letters + 6 digits)
    if (!preg_match('/^[0-9]{2}[A-Za-z]{3}[0-9]{6}$/', $reg)) {
        $error = "❌ Register number must be 2 digits + 3 letters + 6 digits! Example: 11UCA222222";
    } else {
        $check = $conn->query("SELECT * FROM students WHERE reg_no = '$reg' OR email = '$email'");
        if ($check->num_rows > 0) {
            $error = "❌ Register number or Email already exists!";
        } else {
            // Create uploads directory if not exists
            $target_dir = "uploads/students/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Handle passport photo upload
            $photo = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                $photo_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo = $target_dir . 'passport_' . time() . '_' . $reg . '.' . $photo_ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
            }
            
            // Handle ID card photo upload
            $id_card = '';
            if (isset($_FILES['id_card']) && $_FILES['id_card']['error'] == 0) {
                $id_ext = pathinfo($_FILES['id_card']['name'], PATHINFO_EXTENSION);
                $id_card = $target_dir . 'idcard_' . time() . '_' . $reg . '.' . $id_ext;
                move_uploaded_file($_FILES['id_card']['tmp_name'], $id_card);
            }
            
            $conn->query("INSERT INTO students (reg_no, name, department, class_level, year_of_study, email, phone, photo, id_card_photo, password, status) 
                          VALUES ('$reg', '$name', '$dept', '$class_level', $year_of_study, '$email', '$phone', '$photo', '$id_card', '$password', 'pending')");
            $success = "✅ Registration successful! Your account is pending admin approval.";
        }
    }
}

// 👨‍🎓 Admin Add Student (with ID card support and class/level) - Updated Format
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $reg = $conn->real_escape_string($_POST['reg_no']);
    $name = $conn->real_escape_string($_POST['name']);
    $dept = $conn->real_escape_string($_POST['department']);
    $class_level = $conn->real_escape_string($_POST['class_level']);
    $year_of_study = intval($_POST['year_of_study']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = password_hash($_POST['password'] ?? 'student123', PASSWORD_DEFAULT);
    
    // Validate register number format (2 digits + 3 letters + 6 digits)
    if (!preg_match('/^[0-9]{2}[A-Za-z]{3}[0-9]{6}$/', $reg)) {
        $error = "❌ Register number must be 2 digits + 3 letters + 6 digits! Example: 11UCA222222";
    } else {
        $target_dir = "uploads/students/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        // Handle passport photo upload
        $photo = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $photo_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo = $target_dir . 'passport_' . time() . '_' . $reg . '.' . $photo_ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
        }
        
        // Handle ID card photo upload
        $id_card = '';
        if (isset($_FILES['id_card']) && $_FILES['id_card']['error'] == 0) {
            $id_ext = pathinfo($_FILES['id_card']['name'], PATHINFO_EXTENSION);
            $id_card = $target_dir . 'idcard_' . time() . '_' . $reg . '.' . $id_ext;
            move_uploaded_file($_FILES['id_card']['tmp_name'], $id_card);
        }
        
        $conn->query("INSERT INTO students (reg_no, name, department, class_level, year_of_study, email, phone, photo, id_card_photo, password, status) 
                      VALUES ('$reg', '$name', '$dept', '$class_level', $year_of_study, '$email', '$phone', '$photo', '$id_card', '$password', 'active')");
        $success = "✅ Student added successfully!";
    }
}

// ✅ Approve Student
if (isset($_GET['approve_student'])) {
    $id = intval($_GET['approve_student']);
    $conn->query("UPDATE students SET status = 'active' WHERE id = $id");
    header('Location: index.php?page=pending_students');
    exit;
}

// ❌ Reject Student
if (isset($_GET['reject_student'])) {
    $id = intval($_GET['reject_student']);
    $conn->query("DELETE FROM students WHERE id = $id AND status = 'pending'");
    header('Location: index.php?page=pending_students');
    exit;
}

// 📝 Event Handlers with File Uploads
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_event'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $desc = $conn->real_escape_string($_POST['description']);
    $date = $conn->real_escape_string($_POST['event_date']);
    $venue = $conn->real_escape_string($_POST['venue']);
    $max = intval($_POST['max_participants']);
    $notes = $conn->real_escape_string($_POST['event_notes']);
    
    $target_dir = "uploads/events/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Handle event poster upload
    $poster = '';
    if (isset($_FILES['event_poster']) && $_FILES['event_poster']['error'] == 0) {
        $poster_ext = pathinfo($_FILES['event_poster']['name'], PATHINFO_EXTENSION);
        $poster = $target_dir . 'poster_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $title) . '.' . $poster_ext;
        move_uploaded_file($_FILES['event_poster']['tmp_name'], $poster);
    }
    
    // Handle event schedule upload
    $schedule = '';
    if (isset($_FILES['event_schedule']) && $_FILES['event_schedule']['error'] == 0) {
        $schedule_ext = pathinfo($_FILES['event_schedule']['name'], PATHINFO_EXTENSION);
        $schedule = $target_dir . 'schedule_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $title) . '.' . $schedule_ext;
        move_uploaded_file($_FILES['event_schedule']['tmp_name'], $schedule);
    }
    
    $conn->query("INSERT INTO events (title, description, event_date, venue, max_participants, event_notes, event_poster, event_schedule, created_by) 
                  VALUES ('$title', '$desc', '$date', '$venue', $max, '$notes', '$poster', '$schedule', {$_SESSION['user_id']})");
    $success = "✅ Event created successfully with details!";
}

// Update event details
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_event'])) {
    $event_id = intval($_POST['event_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $desc = $conn->real_escape_string($_POST['description']);
    $date = $conn->real_escape_string($_POST['event_date']);
    $venue = $conn->real_escape_string($_POST['venue']);
    $max = intval($_POST['max_participants']);
    $notes = $conn->real_escape_string($_POST['event_notes']);
    
    $target_dir = "uploads/events/";
    
    // Get existing event data
    $event = $conn->query("SELECT * FROM events WHERE id = $event_id")->fetch_assoc();
    $poster = $event['event_poster'];
    $schedule = $event['event_schedule'];
    
    // Handle event poster upload
    if (isset($_FILES['event_poster']) && $_FILES['event_poster']['error'] == 0) {
        // Delete old poster if exists
        if ($poster && file_exists($poster)) {
            unlink($poster);
        }
        $poster_ext = pathinfo($_FILES['event_poster']['name'], PATHINFO_EXTENSION);
        $poster = $target_dir . 'poster_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $title) . '.' . $poster_ext;
        move_uploaded_file($_FILES['event_poster']['tmp_name'], $poster);
    }
    
    // Handle event schedule upload
    if (isset($_FILES['event_schedule']) && $_FILES['event_schedule']['error'] == 0) {
        // Delete old schedule if exists
        if ($schedule && file_exists($schedule)) {
            unlink($schedule);
        }
        $schedule_ext = pathinfo($_FILES['event_schedule']['name'], PATHINFO_EXTENSION);
        $schedule = $target_dir . 'schedule_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $title) . '.' . $schedule_ext;
        move_uploaded_file($_FILES['event_schedule']['tmp_name'], $schedule);
    }
    
    $conn->query("UPDATE events SET 
                  title = '$title', 
                  description = '$desc', 
                  event_date = '$date', 
                  venue = '$venue', 
                  max_participants = $max,
                  event_notes = '$notes',
                  event_poster = '$poster',
                  event_schedule = '$schedule'
                  WHERE id = $event_id");
    $success = "✅ Event updated successfully!";
}

if (isset($_GET['delete_event'])) {
    $id = intval($_GET['delete_event']);
    
    // Delete event files
    $event = $conn->query("SELECT event_poster, event_schedule FROM events WHERE id = $id")->fetch_assoc();
    if ($event['event_poster'] && file_exists($event['event_poster'])) {
        unlink($event['event_poster']);
    }
    if ($event['event_schedule'] && file_exists($event['event_schedule'])) {
        unlink($event['event_schedule']);
    }
    
    $conn->query("DELETE FROM events WHERE id = $id");
    header('Location: index.php?page=events');
    exit;
}

// 📋 Event Registration
if (isset($_GET['register_event'])) {
    $event_id = intval($_GET['register_event']);
    $student_id = $_SESSION['user_id'];
    
    $check = $conn->query("SELECT * FROM registrations WHERE event_id = $event_id AND student_id = $student_id");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO registrations (event_id, student_id, status) VALUES ($event_id, $student_id, 'approved')");
        $success = "✅ Successfully registered for event!";
    } else {
        $error = "❌ Already registered!";
    }
}

// 📊 Attendance Handler - ABSENT students do NOT get certificates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $event_id = intval($_POST['event_id']);
    $date = date('Y-m-d');
    
    foreach ($_POST['attendance'] as $student_id => $status) {
        $student_id = intval($student_id);
        $status = $conn->real_escape_string($status);
        
        $conn->query("INSERT INTO attendance (event_id, student_id, marked_date, status, marked_by) 
                      VALUES ($event_id, $student_id, '$date', '$status', {$_SESSION['user_id']})
                      ON DUPLICATE KEY UPDATE status = '$status'");
        
        // Update registration attendance status
        $conn->query("UPDATE registrations SET attendance_status = '$status' 
                      WHERE event_id = $event_id AND student_id = $student_id");
        
        // If attendance is absent, ensure no certificate is issued
        if ($status == 'absent') {
            $conn->query("UPDATE registrations SET certificate_issued = FALSE, certificate_no = NULL 
                          WHERE event_id = $event_id AND student_id = $student_id");
        }
    }
    $success = "✅ Attendance marked successfully! (Absent students will not receive certificates)";
}

// 📤 Excel Upload for Attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['attendance_excel'])) {
    $file = $_FILES['attendance_excel']['tmp_name'];
    $event_id = intval($_POST['event_id']);
    $date = date('Y-m-d');
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        $header = fgetcsv($handle);
        $success_count = 0;
        $error_count = 0;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $reg_no = $conn->real_escape_string($data[0]);
            $status = isset($data[6]) ? $conn->real_escape_string($data[6]) : 'present';
            
            $student = $conn->query("SELECT id FROM students WHERE reg_no = '$reg_no'")->fetch_assoc();
            if ($student) {
                $student_id = $student['id'];
                
                $conn->query("INSERT INTO attendance (event_id, student_id, marked_date, status, marked_by) 
                              VALUES ($event_id, $student_id, '$date', '$status', {$_SESSION['user_id']})
                              ON DUPLICATE KEY UPDATE status = '$status'");
                
                $conn->query("UPDATE registrations SET attendance_status = '$status' 
                              WHERE event_id = $event_id AND student_id = $student_id");
                
                // If attendance is absent, ensure no certificate is issued
                if ($status == 'absent') {
                    $conn->query("UPDATE registrations SET certificate_issued = FALSE, certificate_no = NULL 
                                  WHERE event_id = $event_id AND student_id = $student_id");
                }
                
                $success_count++;
            } else {
                $error_count++;
            }
        }
        fclose($handle);
        $success = "✅ Attendance imported: $success_count records added, $error_count errors! (Absent students blocked from certificates)";
    }
}

// 🎫 Generate Single Certificate (Only for present/late students)
if (isset($_GET['generate_certificate'])) {
    $registration_id = intval($_GET['generate_certificate']);
    
    $reg = $conn->query("SELECT r.*, e.title as event_title, e.event_date, 
                         s.name as student_name, s.reg_no, s.department, s.class_level, s.year_of_study,
                         a.full_name as admin_name, a.signature as admin_signature
                         FROM registrations r
                         JOIN events e ON r.event_id = e.id
                         JOIN students s ON r.student_id = s.id
                         LEFT JOIN admin a ON a.id = {$_SESSION['user_id']}
                         WHERE r.id = $registration_id")->fetch_assoc();
    
    if ($reg) {
        // Check if student is absent
        if ($reg['attendance_status'] == 'absent') {
            $error = "❌ Cannot generate certificate for absent student!";
        } else {
            $certificate_no = 'CERT-' . date('Y') . '-' . str_pad($registration_id, 6, '0', STR_PAD_LEFT);
            $verification_code = md5($certificate_no . time());
            
            $conn->query("UPDATE registrations SET 
                          certificate_issued = TRUE,
                          certificate_no = '$certificate_no',
                          certificate_issued_date = CURDATE()
                          WHERE id = $registration_id");
            
            $conn->query("INSERT INTO certificates (registration_id, event_id, student_id, 
                          certificate_no, issue_date, verification_code, issued_by,
                          hod_signature)
                          VALUES ($registration_id, {$reg['event_id']}, {$reg['student_id']},
                          '$certificate_no', CURDATE(), '$verification_code', {$_SESSION['user_id']},
                          '{$_SESSION['hod_signature']}')");
            
            $success = "✅ Certificate generated successfully! Certificate No: $certificate_no";
        }
    }
}

// 🎫 MULTIPLE CERTIFICATE GENERATION (Only for present/late students)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_multiple_certificates'])) {
    $event_id = intval($_POST['event_id']);
    $selected_students = $_POST['selected_students'] ?? [];
    
    if (empty($selected_students)) {
        $error = "❌ Please select at least one student!";
    } else {
        $success_count = 0;
        $error_count = 0;
        $existing_count = 0;
        $absent_count = 0;
        
        foreach ($selected_students as $registration_id) {
            $registration_id = intval($registration_id);
            
            $reg = $conn->query("SELECT r.*, e.title as event_title, e.event_date, 
                                 s.name as student_name, s.reg_no, s.department, s.class_level, s.year_of_study
                                 FROM registrations r
                                 JOIN events e ON r.event_id = e.id
                                 JOIN students s ON r.student_id = s.id
                                 WHERE r.id = $registration_id")->fetch_assoc();
            
            if ($reg) {
                $check_cert = $conn->query("SELECT id FROM certificates WHERE registration_id = $registration_id");
                
                if ($check_cert->num_rows == 0) {
                    // Only generate certificates for present or late students
                    if ($reg['attendance_status'] == 'present' || $reg['attendance_status'] == 'late') {
                        $certificate_no = 'CERT-' . date('Y') . '-' . str_pad($registration_id, 6, '0', STR_PAD_LEFT);
                        $verification_code = md5($certificate_no . time() . uniqid());
                        
                        $conn->query("UPDATE registrations SET 
                                      certificate_issued = TRUE,
                                      certificate_no = '$certificate_no',
                                      certificate_issued_date = CURDATE()
                                      WHERE id = $registration_id");
                        
                        $conn->query("INSERT INTO certificates (registration_id, event_id, student_id, 
                                      certificate_no, issue_date, verification_code, issued_by,
                                      hod_signature)
                                      VALUES ($registration_id, {$reg['event_id']}, {$reg['student_id']},
                                      '$certificate_no', CURDATE(), '$verification_code', {$_SESSION['user_id']},
                                      '{$_SESSION['hod_signature']}')");
                        
                        $success_count++;
                    } else {
                        $absent_count++;
                    }
                } else {
                    $existing_count++;
                }
            }
        }
        
        if ($success_count > 0) {
            $success = "✅ Successfully generated $success_count certificates!" . 
                      ($existing_count > 0 ? " ($existing_count already existed)" : "") .
                      ($absent_count > 0 ? " ($absent_count absent students skipped)" : "");
        } else {
            $error = "❌ No certificates were generated. " . 
                    ($absent_count > 0 ? "$absent_count absent students cannot receive certificates." : "");
        }
    }
}

// 📦 Bulk Certificate Download (ZIP) - Only for issued certificates
if (isset($_GET['bulk_download_certificates'])) {
    $event_id = intval($_GET['event_id']);
    
    $event = $conn->query("SELECT title FROM events WHERE id = $event_id")->fetch_assoc();
    $event_title = preg_replace('/[^a-zA-Z0-9]/', '_', $event['title']);
    
    $temp_dir = 'temp_certs_' . time() . '_' . $event_id;
    if (!file_exists($temp_dir)) {
        mkdir($temp_dir, 0777, true);
    }
    
    $certs = $conn->query("SELECT c.*, s.name as student_name, s.reg_no, s.department, s.class_level, s.year_of_study,
                                  e.title as event_title, e.event_date, a.signature as admin_signature
                           FROM certificates c
                           JOIN students s ON c.student_id = s.id
                           JOIN events e ON c.event_id = e.id
                           LEFT JOIN admin a ON c.issued_by = a.id
                           WHERE c.event_id = $event_id
                           ORDER BY s.name");
    
    if ($certs->num_rows > 0) {
        while ($cert = $certs->fetch_assoc()) {
            $filename = $temp_dir . '/' . preg_replace('/[^a-zA-Z0-9]/', '_', $cert['student_name']) . '_' . $cert['certificate_no'] . '.html';
            
            $hod_sig = $cert['hod_signature'] ? '<img src="' . $cert['hod_signature'] . '" style="height: 50px;">' : '__________________';
            
            $html = '<!DOCTYPE html>
            <html>
            <head>
                <title>Certificate of Participation</title>
                <style>
                    body {
                        font-family: "Times New Roman", serif;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        min-height: 100vh;
                        margin: 0;
                        padding: 20px;
                    }
                    .certificate {
                        width: 1000px;
                        height: 700px;
                        background: white;
                        border: 20px solid #764ba2;
                        padding: 40px;
                        position: relative;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                        border-radius: 20px;
                        margin: 0 auto;
                    }
                    .certificate::before {
                        content: "🎓";
                        font-size: 80px;
                        position: absolute;
                        top: 20px;
                        left: 20px;
                        opacity: 0.1;
                    }
                    .certificate::after {
                        content: "🎓";
                        font-size: 80px;
                        position: absolute;
                        bottom: 20px;
                        right: 20px;
                        opacity: 0.1;
                    }
                    .college-name {
                        text-align: center;
                        font-size: 28px;
                        color: #764ba2;
                        font-weight: bold;
                        margin-bottom: 5px;
                        text-transform: uppercase;
                    }
                    .college-location {
                        text-align: center;
                        font-size: 16px;
                        color: #667eea;
                        margin-bottom: 5px;
                    }
                    .department-header {
                        text-align: center;
                        font-size: 22px;
                        color: #764ba2;
                        font-weight: bold;
                        margin-bottom: 15px;
                        padding-bottom: 10px;
                        border-bottom: 2px solid #667eea;
                        text-transform: uppercase;
                        letter-spacing: 2px;
                    }
                    h1 {
                        color: #764ba2;
                        font-size: 48px;
                        text-align: center;
                        margin-bottom: 20px;
                        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
                    }
                    .subtitle {
                        text-align: center;
                        font-size: 24px;
                        color: #667eea;
                        border-bottom: 2px solid #764ba2;
                        padding-bottom: 20px;
                    }
                    .content {
                        text-align: center;
                        font-size: 20px;
                        margin: 40px 0;
                        line-height: 2;
                    }
                    .student-name {
                        font-size: 36px;
                        color: #764ba2;
                        font-weight: bold;
                        margin: 20px 0;
                        text-transform: uppercase;
                    }
                    .student-info {
                        font-size: 18px;
                        color: #555;
                        margin: 10px 0;
                    }
                    .event-name {
                        font-size: 28px;
                        color: #764ba2;
                        font-weight: bold;
                    }
                    .footer {
                        display: flex;
                        justify-content: space-between;
                        margin-top: 60px;
                        padding-top: 20px;
                        border-top: 2px solid #764ba2;
                    }
                    .signature {
                        text-align: center;
                    }
                    .signature img {
                        max-height: 50px;
                        margin-bottom: 5px;
                    }
                    .verification {
                        position: absolute;
                        bottom: 30px;
                        right: 40px;
                        font-size: 12px;
                        color: gray;
                    }
                    .seal {
                        font-size: 60px;
                        position: absolute;
                        right: 80px;
                        bottom: 100px;
                        opacity: 0.2;
                    }
                    .dept-badge {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        padding: 5px 20px;
                        border-radius: 50px;
                        display: inline-block;
                        margin: 10px 0;
                    }
                    .print-button {
                        text-align: center;
                        margin-top: 20px;
                    }
                    .print-button button {
                        padding: 10px 30px;
                        font-size: 16px;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        border: none;
                        border-radius: 50px;
                        cursor: pointer;
                    }
                    .print-button button:hover {
                        transform: scale(1.05);
                    }
                    @media print {
                        body { background: white; }
                        .certificate { border: 20px solid #764ba2; box-shadow: none; }
                        .print-button { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="certificate">
                    <div class="college-name">🎓 GOVERNMENT ARTS & SCIENCE COLLEGE (AUTONOMOUS)</div>
                    <div class="college-location">SALEM - 636007, TAMIL NADU</div>
                    <div class="department-header">DEPARTMENT OF ' . strtoupper($cert['department']) . '</div>
                    
                    <h1>Certificate of Participation</h1>
                    <div class="subtitle">This is proudly presented to</div>
                    <div class="content">
                        <div class="student-name">' . $cert['student_name'] . '</div>
                        <div class="student-info">Register Number: ' . $cert['reg_no'] . '</div>
                        <div class="student-info">Class: ' . $cert['class_level'] . ' - Year ' . $cert['year_of_study'] . '</div>
                        <div class="dept-badge">' . $cert['department'] . '</div>
                        <br>
                        <div>for successfully participating in</div>
                        <div class="event-name">' . $cert['event_title'] . '</div>
                        <div>held on ' . date('d F Y', strtotime($cert['event_date'])) . '</div>
                    </div>
                    <div class="footer">
                        <div class="signature">
                            ' . $hod_sig . '
                            <div>HOD</div>
                            <div>Department of ' . $cert['department'] . '</div>
                        </div>
                    </div>
                    <div class="verification">
                        Certificate No: ' . $cert['certificate_no'] . '<br>
                        Verification Code: ' . $cert['verification_code'] . '<br>
                        Issued on: ' . date('d F Y', strtotime($cert['issue_date'])) . '
                    </div>
                    <div class="seal">🔵</div>
                </div>
                <div class="print-button">
                    <button onclick="window.print()">🖨️ Print Certificate</button>
                </div>
            </body>
            </html>';
            
            file_put_contents($filename, $html);
        }
        
        $zip_file = 'certificates_' . $event_title . '_' . date('Y-m-d') . '.zip';
        
        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = glob($temp_dir . '/*');
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            
            array_map('unlink', glob("$temp_dir/*.*"));
            rmdir($temp_dir);
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zip_file . '"');
            header('Content-Length: ' . filesize($zip_file));
            readfile($zip_file);
            
            unlink($zip_file);
            exit;
        } else {
            $error = "❌ Failed to create ZIP file!";
        }
    } else {
        $error = "❌ No certificates found for this event!";
        if (file_exists($temp_dir)) {
            array_map('unlink', glob("$temp_dir/*.*"));
            rmdir($temp_dir);
        }
    }
}

// 📊 Export Excel Report
if (isset($_GET['export_report'])) {
    $type = $_GET['type'] ?? 'event';
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="event_report_'.date('Y-m-d').'.xls"');
    
    if ($type == 'event' && $id > 0) {
        $event = $conn->query("SELECT * FROM events WHERE id = $id")->fetch_assoc();
        $participants = $conn->query("SELECT s.reg_no, s.name, s.department, s.class_level, s.year_of_study, s.email, 
                                     r.registration_date, r.attendance_status, r.certificate_issued,
                                     r.certificate_no
                                     FROM registrations r
                                     JOIN students s ON r.student_id = s.id
                                     WHERE r.event_id = $id AND r.status = 'approved'");
        
        echo "📅 EVENT PARTICIPATION REPORT\n";
        echo "Event: " . $event['title'] . "\n";
        echo "Date: " . $event['event_date'] . "\n";
        echo "Venue: " . $event['venue'] . "\n\n";
        
        echo "Register No\tName\tDepartment\tClass\tYear\tEmail\tRegistration Date\tAttendance\tCertificate\tCertificate No\n";
        while($p = $participants->fetch_assoc()) {
            echo $p['reg_no'] . "\t" . $p['name'] . "\t" . $p['department'] . "\t" . 
                 $p['class_level'] . "\t" . $p['year_of_study'] . "\t" . 
                 $p['email'] . "\t" . $p['registration_date'] . "\t" . 
                 $p['attendance_status'] . "\t" . ($p['certificate_issued'] ? 'Yes' : 'No') . "\t" . 
                 ($p['certificate_no'] ?? 'N/A') . "\n";
        }
    }
    exit;
}

// 📥 Download Certificate with Print Option
if (isset($_GET['download_certificate'])) {
    $cert_id = intval($_GET['download_certificate']);
    
    $cert = $conn->query("SELECT c.*, e.title as event_title, e.event_date, 
                          s.name as student_name, s.reg_no, s.department, s.class_level, s.year_of_study,
                          a.full_name as issued_by_name, a.signature as admin_signature
                          FROM certificates c
                          JOIN events e ON c.event_id = e.id
                          JOIN students s ON c.student_id = s.id
                          LEFT JOIN admin a ON c.issued_by = a.id
                          WHERE c.id = $cert_id")->fetch_assoc();
    
    if ($cert) {
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="certificate_' . $cert['certificate_no'] . '.html"');
        
        $hod_sig = $cert['hod_signature'] ? '<img src="' . $cert['hod_signature'] . '" style="height: 50px;">' : '__________________';
        
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Certificate of Participation</title>
            <style>
                body {
                    font-family: "Times New Roman", serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                }
                .certificate {
                    width: 1000px;
                    height: 700px;
                    background: white;
                    border: 20px solid #764ba2;
                    padding: 40px;
                    position: relative;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    border-radius: 20px;
                }
                .certificate::before {
                    content: "🎓";
                    font-size: 80px;
                    position: absolute;
                    top: 20px;
                    left: 20px;
                    opacity: 0.1;
                }
                .certificate::after {
                    content: "🎓";
                    font-size: 80px;
                    position: absolute;
                    bottom: 20px;
                    right: 20px;
                    opacity: 0.1;
                }
                .college-name {
                    text-align: center;
                    font-size: 28px;
                    color: #764ba2;
                    font-weight: bold;
                    margin-bottom: 5px;
                    text-transform: uppercase;
                }
                .college-location {
                    text-align: center;
                    font-size: 16px;
                    color: #667eea;
                    margin-bottom: 5px;
                }
                .department-header {
                    text-align: center;
                    font-size: 22px;
                    color: #764ba2;
                    font-weight: bold;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #667eea;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }
                h1 {
                    color: #764ba2;
                    font-size: 48px;
                    text-align: center;
                    margin-bottom: 20px;
                    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
                }
                .subtitle {
                    text-align: center;
                    font-size: 24px;
                    color: #667eea;
                    border-bottom: 2px solid #764ba2;
                    padding-bottom: 20px;
                }
                .content {
                    text-align: center;
                    font-size: 20px;
                    margin: 40px 0;
                    line-height: 2;
                }
                .student-name {
                    font-size: 36px;
                    color: #764ba2;
                    font-weight: bold;
                    margin: 20px 0;
                    text-transform: uppercase;
                }
                .student-info {
                    font-size: 18px;
                    color: #555;
                    margin: 10px 0;
                }
                .event-name {
                    font-size: 28px;
                    color: #764ba2;
                    font-weight: bold;
                }
                .footer {
                    display: flex;
                    justify-content: space-between;
                    margin-top: 60px;
                    padding-top: 20px;
                    border-top: 2px solid #764ba2;
                }
                .signature {
                    text-align: center;
                }
                .signature img {
                    max-height: 50px;
                    margin-bottom: 5px;
                }
                .verification {
                    position: absolute;
                    bottom: 30px;
                    right: 40px;
                    font-size: 12px;
                    color: gray;
                }
                .seal {
                    font-size: 60px;
                    position: absolute;
                    right: 80px;
                    bottom: 100px;
                    opacity: 0.2;
                }
                .dept-badge {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 5px 20px;
                    border-radius: 50px;
                    display: inline-block;
                    margin: 10px 0;
                }
                .print-button {
                    text-align: center;
                    margin-top: 20px;
                }
                .print-button button {
                    padding: 10px 30px;
                    font-size: 16px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    border-radius: 50px;
                    cursor: pointer;
                }
                .print-button button:hover {
                    transform: scale(1.05);
                }
                @media print {
                    body { background: white; }
                    .certificate { border: 20px solid #764ba2; box-shadow: none; }
                    .print-button { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="certificate">
                <div class="college-name">🎓 GOVERNMENT ARTS & SCIENCE COLLEGE (AUTONOMOUS)</div>
                <div class="college-location">SALEM - 636007, TAMIL NADU</div>
                <div class="department-header">DEPARTMENT OF ' . strtoupper($cert['department']) . '</div>
                
                <h1>Certificate of Participation</h1>
                <div class="subtitle">This is proudly presented to</div>
                <div class="content">
                    <div class="student-name">' . $cert['student_name'] . '</div>
                    <div class="student-info">Register Number: ' . $cert['reg_no'] . '</div>
                    <div class="student-info">Class: ' . $cert['class_level'] . ' - Year ' . $cert['year_of_study'] . '</div>
                    <div class="dept-badge">' . $cert['department'] . '</div>
                    <br>
                    <div>for successfully participating in</div>
                    <div class="event-name">' . $cert['event_title'] . '</div>
                    <div>held on ' . date('d F Y', strtotime($cert['event_date'])) . '</div>
                </div>
                <div class="footer">
                    <div class="signature">
                        ' . $hod_sig . '
                        <div>HOD</div>
                        <div>Department of ' . $cert['department'] . '</div>
                    </div>
                </div>
                <div class="verification">
                    Certificate No: ' . $cert['certificate_no'] . '<br>
                    Verification Code: ' . $cert['verification_code'] . '<br>
                    Issued on: ' . date('d F Y', strtotime($cert['issue_date'])) . '
                </div>
                <div class="seal">🔵</div>
            </div>
            <div class="print-button">
                <button onclick="window.print()">🖨️ Print Certificate</button>
            </div>
        </body>
        </html>';
        exit;
    }
}

// 👁️ View Attendance Excel Format - Updated with new register number format
if (isset($_GET['view_attendance_format'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="attendance_template.csv"');
    
    echo "Register No,Name,Department,Class,Year,Email,Status\n";
    echo "11UCA222222,John Doe,Computer Application (BCA),UG,3,john@college.edu,present\n";
    echo "11UCS333333,Jane Smith,Computer Science,PG,2,jane@college.edu,absent\n";
    echo "11UEL444444,Mike Johnson,English,UG,1,mike@college.edu,late\n";
    exit;
}

// 📤 Export Students to Excel
if (isset($_GET['export_students'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="students_list_'.date('Y-m-d').'.xls"');
    
    $dept_filter = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';
    $status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
    $class_filter = isset($_GET['class_level']) ? $conn->real_escape_string($_GET['class_level']) : '';
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    
    $query = "SELECT reg_no, name, department, class_level, year_of_study, email, phone, status, created_at 
              FROM students WHERE 1=1";
    
    if($dept_filter) $query .= " AND department = '$dept_filter'";
    if($status_filter) $query .= " AND status = '$status_filter'";
    if($class_filter) $query .= " AND class_level = '$class_filter'";
    if($search) $query .= " AND (name LIKE '%$search%' OR reg_no LIKE '%$search%' OR email LIKE '%$search%')";
    
    $query .= " ORDER BY created_at DESC";
    
    $students = $conn->query($query);
    
    echo "Register Number\tName\tDepartment\tClass\tYear\tEmail\tPhone\tStatus\tRegistered Date\n";
    while($s = $students->fetch_assoc()) {
        echo $s['reg_no'] . "\t" . $s['name'] . "\t" . $s['department'] . "\t" . 
             $s['class_level'] . "\t" . $s['year_of_study'] . "\t" . 
             $s['email'] . "\t" . $s['phone'] . "\t" . $s['status'] . "\t" . 
             date('d-m-Y', strtotime($s['created_at'])) . "\n";
    }
    exit;
}

// 📦 Bulk Certificates Page
if ($page == 'bulk_certificates' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    if ($event_id > 0) {
        $event = $conn->query("SELECT * FROM events WHERE id = $event_id")->fetch_assoc();
        
        $students = $conn->query("SELECT s.*, r.id as registration_id, r.attendance_status, 
                                         r.certificate_issued, r.certificate_no
                                  FROM students s
                                  JOIN registrations r ON s.id = r.student_id
                                  WHERE r.event_id = $event_id AND s.status = 'active'
                                  ORDER BY s.name");
    }
    
    $events = $conn->query("SELECT id, title, event_date FROM events ORDER BY event_date DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎓 College Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .navbar {
            background: rgba(255,255,255,0.98) !important;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        .navbar-brand { 
            font-size: 1.8rem; 
            font-weight: bold; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .nav-link { 
            color: #555 !important; 
            font-weight: 500; 
            transition: 0.3s;
            position: relative;
        }
        .nav-link:hover { 
            color: #764ba2 !important; 
            transform: translateY(-2px); 
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: 0.3s;
            transform: translateX(-50%);
        }
        .nav-link:hover::after {
            width: 80%;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
            overflow: hidden;
        }
        .card:hover { 
            transform: translateY(-15px) scale(1.02); 
            box-shadow: 0 30px 70px rgba(0,0,0,0.2); 
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
            border-radius: 20px 20px 0 0 !important;
            padding: 20px 25px;
            font-size: 1.3rem;
            border-bottom: none;
        }
        .btn {
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.2);
            transition: 0.3s;
        }
        .btn:hover::before {
            left: 0;
        }
        .btn-primary { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            border: none; 
        }
        .btn-primary:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 10px 25px rgba(102,126,234,0.5); 
        }
        .btn-success { 
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); 
            border: none; 
            color: #333;
        }
        .btn-danger { 
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); 
            border: none; 
        }
        .btn-warning {
            background: linear-gradient(135deg, #ffe985 0%, #fa742b 100%);
            border: none;
            color: #333;
        }
        .btn-info {
            background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%);
            border: none;
            color: #333;
        }
        .btn-back {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            border: none;
            color: white;
            margin-bottom: 20px;
        }
        .btn-back:hover {
            transform: translateX(-5px);
            box-shadow: 0 10px 25px rgba(127, 140, 141, 0.5);
        }
        .dashboard-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }
        .dashboard-card::before {
            content: '🎓';
            font-size: 80px;
            position: absolute;
            bottom: -20px;
            right: -20px;
            opacity: 0.2;
            transform: rotate(-15deg);
        }
        .stat-icon { 
            font-size: 3.5rem; 
            opacity: 0.3; 
            position: absolute; 
            right: 25px; 
            top: 20px; 
            transition: 0.3s;
        }
        .dashboard-card:hover .stat-icon {
            transform: scale(1.2);
            opacity: 0.5;
        }
        .table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0,0,0,0.05);
        }
        .table thead { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
        }
        .table thead th {
            border-bottom: none;
            padding: 15px;
        }
        .alert { 
            border-radius: 15px; 
            border: none;
            animation: slideIn 0.5s;
        }
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .footer {
            background: rgba(255,255,255,0.98);
            padding: 30px;
            text-align: center;
            margin-top: 50px;
            border-radius: 30px 30px 0 0;
            backdrop-filter: blur(10px);
        }
        .emoji-icon { margin-right: 10px; }
        .event-badge {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            padding: 8px 20px;
            border-radius: 50px;
            color: #333;
            font-weight: 600;
            display: inline-block;
        }
        .pending-badge {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
        }
        .certificate-badge {
            background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
        }
        .progress {
            height: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .progress-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 5px;
        }
        .attendance-present { color: #28a745; font-weight: bold; }
        .attendance-absent { color: #dc3545; font-weight: bold; }
        .attendance-late { color: #ffc107; font-weight: bold; }
        
        .certificate-preview {
            background: white;
            padding: 30px;
            border-radius: 15px;
            border: 3px solid #764ba2;
            position: relative;
        }
        .certificate-preview::before {
            content: '🎓';
            font-size: 100px;
            position: absolute;
            top: 20px;
            left: 20px;
            opacity: 0.1;
        }
        
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #764ba2;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .hover-scale {
            transition: transform 0.3s;
        }
        .hover-scale:hover {
            transform: scale(1.05);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }
        
        .student-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .select-all-container {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .student-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #764ba2;
            cursor: pointer;
        }
        .student-id-card {
            max-width: 100%;
            max-height: 400px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .student-initial {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        .btn-group-vertical .btn {
            margin-bottom: 2px;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .pagination .page-link {
            border-radius: 50%;
            margin: 0 3px;
            color: #764ba2;
        }
        .pagination .active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #764ba2;
        }
        .photo-upload-area {
            border: 2px dashed #764ba2;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .photo-upload-area:hover {
            background: #f8f9fa;
            border-color: #667eea;
        }
        .photo-upload-area i {
            font-size: 48px;
            color: #764ba2;
        }
        .id-card-link {
            font-size: 12px;
            color: #667eea;
            text-decoration: none;
            display: inline-block;
            margin-top: 5px;
        }
        .id-card-link:hover {
            text-decoration: underline;
            color: #764ba2;
        }
        .back-button-container {
            margin-bottom: 20px;
        }
        .back-button-container .btn {
            padding: 8px 20px;
            font-size: 0.9rem;
        }
        .search-box {
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255,255,255,0.9);
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .search-box input {
            border: none;
            background: transparent;
            padding: 10px 20px;
            width: 100%;
            outline: none;
        }
        .search-box button {
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 30px;
            border-radius: 50px;
        }
        .event-detail-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .event-detail-card img {
            max-width: 100%;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .event-detail-card .download-btn {
            display: inline-block;
            padding: 8px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            margin: 5px;
        }
        .event-detail-card .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        .print-btn {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            cursor: pointer;
            margin: 5px;
        }
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(127,140,141,0.4);
        }
        .excel-upload-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .excel-upload-section .btn {
            background: white;
            color: #28a745;
        }
        .excel-upload-section .btn:hover {
            background: #f8f9fa;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- 🎪 Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap emoji-icon"></i>EventMaster
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['role'])): ?>
                        <?php if($_SESSION['role'] == 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=events"><i class="fas fa-calendar-alt"></i> Events</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=students"><i class="fas fa-users"></i> Students</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=pending_students"><i class="fas fa-clock"></i> Pending</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=reports"><i class="fas fa-chart-bar"></i> Reports</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=certificates"><i class="fas fa-certificate"></i> Certificates</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=bulk_certificates"><i class="fas fa-layer-group"></i> Bulk Certificates</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=signatures"><i class="fas fa-signature"></i> Signatures</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=excel_upload"><i class="fas fa-file-excel"></i> Excel Upload</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=student_dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=available_events"><i class="fas fa-calendar-check"></i> Events</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=my_registrations"><i class="fas fa-clipboard-list"></i> My Events</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=my_certificates"><i class="fas fa-certificate"></i> Certificates</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout (<?= $_SESSION['username'] ?>)</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=register"><i class="fas fa-user-plus"></i> Student Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <!-- Back Button (shown on all pages except home and login) -->
        <?php if($page != 'home' && $page != 'login' && !isset($_SESSION['role'])): ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        <?php endif; ?>

        <?php if(isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- 🏠 Home Page -->
        <?php if($page == 'home' && !isset($_SESSION['role'])): ?>
            <div class="row min-vh-100 align-items-center">
                <div class="col-md-6">
                    <div class="card p-5 animate__animated animate__fadeInLeft">
                        <h1 class="display-4 mb-4"><i class="fas fa-graduation-cap gradient-text"></i> College Event Management</h1>
                        <p class="lead mb-4">✨ Streamline your college events, registrations, attendance tracking, and certificate generation with our comprehensive system.</p>
                        <div class="d-flex gap-3">
                            <a href="index.php?page=login" class="btn btn-primary btn-lg"><i class="fas fa-sign-in-alt"></i> Login</a>
                            <a href="index.php?page=register" class="btn btn-success btn-lg"><i class="fas fa-user-plus"></i> Student Register</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-4 animate__animated animate__fadeInRight">
                        <div id="demoCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Event Management" class="img-fluid">
                                </div>
                                <div class="carousel-item">
                                    <img src="https://cdn-icons-png.flaticon.com/512/2972/2972342.png" alt="Certificates" class="img-fluid">
                                </div>
                                <div class="carousel-item">
                                    <img src="https://cdn-icons-png.flaticon.com/512/3050/3050525.png" alt="Attendance" class="img-fluid">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="features" class="row mt-5">
                <div class="col-md-3">
                    <div class="card text-center p-4 h-100 hover-scale">
                        <i class="fas fa-calendar-alt fa-4x text-primary mb-3"></i>
                        <h3>Event Management</h3>
                        <p>Create, edit, and manage events with ease</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-4 h-100 hover-scale">
                        <i class="fas fa-users fa-4x text-success mb-3"></i>
                        <h3>Student Management</h3>
                        <p>Add students with photos and track participation</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-4 h-100 hover-scale">
                        <i class="fas fa-clipboard-check fa-4x text-info mb-3"></i>
                        <h3>Attendance Tracking</h3>
                        <p>Mark attendance and generate reports</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-4 h-100 hover-scale">
                        <i class="fas fa-certificate fa-4x text-warning mb-3"></i>
                        <h3>Certificates</h3>
                        <p>Generate and download digital certificates</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 📝 Enhanced Registration Page with Passport Photo, ID Card & Class/Level (Updated Format) -->
        <?php if($page == 'register' && !isset($_SESSION['role'])): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card animate__animated animate__fadeInUp">
                        <div class="card-header text-center">
                            <h3><i class="fas fa-user-graduate"></i> Student Registration</h3>
                            <p class="text-white-50 mb-0">Create your account - Admin approval required</p>
                        </div>
                        <div class="card-body p-5">
                            <form method="POST" enctype="multipart/form-data" id="registrationForm" onsubmit="return validateRegistrationForm()">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-id-card"></i> Register Number *</label>
                                        <input type="text" name="reg_no" class="form-control form-control-lg" 
                                               placeholder="e.g., 11UCA222222" required 
                                               pattern="[0-9]{2}[A-Za-z]{3}[0-9]{6}" 
                                               title="Register number must be 2 digits + 3 letters + 6 digits (e.g., 11UCA222222)">
                                        <small class="text-muted">Format: 2 digits + 3 letters + 6 numbers (e.g., 11UCA222222)</small>
                                        <div id="regNoError" class="text-danger small" style="display: none;"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-user"></i> Full Name *</label>
                                        <input type="text" name="name" class="form-control form-control-lg" placeholder="Enter your full name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-building"></i> Department *</label>
                                        <select name="department" class="form-control form-control-lg" required>
                                            <option value="">Select Department</option>
                                            <option value="Mathematics">Mathematics</option>
                                            <option value="Computer Application (BCA)">Computer Application (BCA)</option>
                                            <option value="Statistics">Statistics</option>
                                            <option value="Computer Science">Computer Science</option>
                                            <option value="English">English</option>
                                            <option value="Tamil">Tamil</option>
                                            <option value="Chemistry">Chemistry</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-layer-group"></i> Class Level *</label>
                                        <select name="class_level" class="form-control form-control-lg" required>
                                            <option value="">Select Class</option>
                                            <option value="UG">UG (Under Graduate)</option>
                                            <option value="PG">PG (Post Graduate)</option>
                                            <option value="Diploma">Diploma</option>
                                            <option value="Certificate">Certificate</option>
                                            <option value="Research">Research</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Year of Study *</label>
                                        <select name="year_of_study" class="form-control form-control-lg" required>
                                            <option value="">Select Year</option>
                                            <option value="1">1st Year</option>
                                            <option value="2">2nd Year</option>
                                            <option value="3">3rd Year</option>
                                            <option value="4">4th Year</option>
                                            <option value="5">5th Year</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-envelope"></i> Email *</label>
                                        <input type="email" name="email" class="form-control form-control-lg" placeholder="your@email.com" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-phone"></i> Phone Number</label>
                                        <input type="tel" name="phone" class="form-control form-control-lg" placeholder="9876543210" pattern="[0-9]{10}" title="10 digit phone number">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-lock"></i> Password *</label>
                                        <input type="password" name="password" class="form-control form-control-lg" placeholder="Create password" required minlength="6">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-camera"></i> Passport Size Photo *</label>
                                        <input type="file" name="photo" class="form-control form-control-lg" accept="image/jpeg,image/png,image/jpg" required id="passportPhoto">
                                        <small class="text-muted">JPG, PNG only. Max 2MB. Passport size (2x2 inch)</small>
                                        <div id="photoPreview" class="mt-2 text-center" style="display: none;">
                                            <img id="previewImage" src="#" alt="Preview" style="max-width: 150px; max-height: 150px; border: 2px solid #764ba2; border-radius: 10px;">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-id-card"></i> ID Card Photo *</label>
                                        <input type="file" name="id_card" class="form-control form-control-lg" accept="image/jpeg,image/png,image/jpg" required id="idCardPhoto">
                                        <small class="text-muted">Upload clear photo of your college ID card (JPG/PNG, Max 5MB)</small>
                                        <div id="idCardPreview" class="mt-2 text-center" style="display: none;">
                                            <img id="previewIdCard" src="#" alt="ID Card Preview" style="max-width: 200px; max-height: 150px; border: 2px solid #764ba2; border-radius: 10px;">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="card bg-light p-3">
                                            <h6 class="mb-3">📋 Registration Number Format Guide:</h6>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <span class="badge bg-primary">Year: 11</span> - First 2 digits (Year of joining)
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="badge bg-success">Course: UCA</span> - Next 3 letters (Course code)
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="badge bg-info">Number: 222222</span> - Last 6 digits (Unique number)
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="badge bg-warning">Class/Year</span> - Select your class and year
                                                </div>
                                            </div>
                                            <div class="mt-2 text-center">
                                                <strong>Example: 11UCA222222</strong> (Year 2011, BCA Course, Roll No 222222)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="#">Terms and Conditions</a> and confirm that the information provided is correct
                                    </label>
                                </div>
                                
                                <button type="submit" name="register_student" class="btn btn-primary btn-lg w-100" id="submitBtn">
                                    <i class="fas fa-user-check"></i> Register Account
                                </button>
                                
                                <div class="text-center mt-4">
                                    <p class="mb-0">Already have an account? <a href="index.php?page=login" class="text-primary">Login here</a></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function validateRegistrationForm() {
                    // Register number validation (2 digits + 3 letters + 6 digits)
                    const regNo = document.querySelector('input[name="reg_no"]').value;
                    const regNoPattern = /^[0-9]{2}[A-Za-z]{3}[0-9]{6}$/;
                    
                    if (!regNoPattern.test(regNo)) {
                        alert('❌ Register number must be 2 digits + 3 letters + 6 digits!\nExample: 11UCA222222');
                        document.querySelector('input[name="reg_no"]').focus();
                        return false;
                    }
                    
                    // Check if register number has correct format
                    const yearPart = regNo.substring(0, 2);
                    const coursePart = regNo.substring(2, 5);
                    const numberPart = regNo.substring(5);
                    
                    if (!/^\d{2}$/.test(yearPart)) {
                        alert('❌ First 2 characters must be digits (Year of joining)');
                        return false;
                    }
                    
                    if (!/^[A-Za-z]{3}$/.test(coursePart)) {
                        alert('❌ Characters 3-5 must be letters (Course code)');
                        return false;
                    }
                    
                    if (!/^\d{6}$/.test(numberPart)) {
                        alert('❌ Last 6 characters must be digits (Unique number)');
                        return false;
                    }
                    
                    // Email validation
                    const email = document.querySelector('input[name="email"]').value;
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(email)) {
                        alert('❌ Please enter a valid email address!');
                        document.querySelector('input[name="email"]').focus();
                        return false;
                    }
                    
                    // Phone validation (if provided)
                    const phone = document.querySelector('input[name="phone"]').value;
                    if (phone && !/^\d{10}$/.test(phone)) {
                        alert('❌ Phone number must be 10 digits!');
                        document.querySelector('input[name="phone"]').focus();
                        return false;
                    }
                    
                    // Password validation
                    const password = document.querySelector('input[name="password"]').value;
                    if (password.length < 6) {
                        alert('❌ Password must be at least 6 characters long!');
                        document.querySelector('input[name="password"]').focus();
                        return false;
                    }
                    
                    // Class level validation
                    const classLevel = document.querySelector('select[name="class_level"]').value;
                    if (!classLevel) {
                        alert('❌ Please select your class level!');
                        return false;
                    }
                    
                    // Year of study validation
                    const yearOfStudy = document.querySelector('select[name="year_of_study"]').value;
                    if (!yearOfStudy) {
                        alert('❌ Please select your year of study!');
                        return false;
                    }
                    
                    // Photo validation
                    const photo = document.getElementById('passportPhoto').files[0];
                    if (photo) {
                        // Check file size (max 2MB)
                        if (photo.size > 2 * 1024 * 1024) {
                            alert('❌ Passport photo must be less than 2MB!');
                            return false;
                        }
                        
                        // Check file type
                        if (!photo.type.match('image.*')) {
                            alert('❌ Please upload an image file for passport photo!');
                            return false;
                        }
                    }
                    
                    // ID Card validation
                    const idCard = document.getElementById('idCardPhoto').files[0];
                    if (idCard) {
                        // Check file size (max 5MB)
                        if (idCard.size > 5 * 1024 * 1024) {
                            alert('❌ ID card photo must be less than 5MB!');
                            return false;
                        }
                        
                        // Check file type
                        if (!idCard.type.match('image.*')) {
                            alert('❌ Please upload an image file for ID card!');
                            return false;
                        }
                    }
                    
                    return true;
                }
                
                // Preview passport photo
                document.getElementById('passportPhoto').addEventListener('change', function(e) {
                    const preview = document.getElementById('photoPreview');
                    const img = document.getElementById('previewImage');
                    
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            img.src = e.target.result;
                            preview.style.display = 'block';
                        }
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                });
                
                // Preview ID card photo
                document.getElementById('idCardPhoto').addEventListener('change', function(e) {
                    const preview = document.getElementById('idCardPreview');
                    const img = document.getElementById('previewIdCard');
                    
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            img.src = e.target.result;
                            preview.style.display = 'block';
                        }
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                });
                
                // Real-time register number validation
                document.querySelector('input[name="reg_no"]').addEventListener('input', function(e) {
                    const value = this.value;
                    const errorDiv = document.getElementById('regNoError');
                    const pattern = /^[A-Za-z0-9]*$/;
                    
                    if (!pattern.test(value)) {
                        errorDiv.textContent = '❌ Only letters and numbers allowed';
                        errorDiv.style.display = 'block';
                    } else if (value.length > 11) {
                        errorDiv.textContent = '❌ Maximum 11 characters allowed (2 digits + 3 letters + 6 digits)';
                        errorDiv.style.display = 'block';
                    } else if (value.length > 0 && value.length < 2) {
                        errorDiv.textContent = '⏳ Enter at least 2 digits...';
                        errorDiv.style.display = 'block';
                    } else if (value.length >= 2) {
                        const yearPart = value.substring(0, 2);
                        const coursePart = value.length >= 5 ? value.substring(2, 5) : '';
                        const numbersPart = value.length > 5 ? value.substring(5) : '';
                        
                        if (value.length <= 2) {
                            if (!/^\d{2}$/.test(yearPart)) {
                                errorDiv.textContent = '❌ First 2 characters must be digits';
                                errorDiv.style.display = 'block';
                            } else {
                                errorDiv.textContent = '✅ Year part valid, now enter 3 letters...';
                                errorDiv.style.color = 'green';
                                errorDiv.style.display = 'block';
                            }
                        } else if (value.length <= 5) {
                            if (!/^[A-Za-z]{1,3}$/.test(coursePart)) {
                                errorDiv.textContent = '❌ Characters 3-5 must be letters';
                                errorDiv.style.display = 'block';
                            } else if (value.length === 5) {
                                errorDiv.textContent = '✅ Course code valid, now enter 6 digits...';
                                errorDiv.style.color = 'green';
                                errorDiv.style.display = 'block';
                            } else {
                                errorDiv.textContent = `⏳ Need ${3 - coursePart.length} more letters...`;
                                errorDiv.style.color = 'orange';
                                errorDiv.style.display = 'block';
                            }
                        } else if (value.length > 5) {
                            if (!/^\d*$/.test(numbersPart)) {
                                errorDiv.textContent = '❌ After 5 characters, only digits allowed';
                                errorDiv.style.display = 'block';
                            } else if (value.length === 11) {
                                errorDiv.textContent = '✅ Valid format!';
                                errorDiv.style.color = 'green';
                                errorDiv.style.display = 'block';
                            } else {
                                errorDiv.textContent = `⏳ Need ${6 - numbersPart.length} more digits...`;
                                errorDiv.style.color = 'orange';
                                errorDiv.style.display = 'block';
                            }
                        }
                    } else {
                        errorDiv.style.display = 'none';
                    }
                });
            </script>
        <?php endif; ?>

        <!-- 🔐 Login Page - NO DEMO CREDENTIALS DISPLAYED -->
        <?php if($page == 'login' && !isset($_SESSION['role'])): ?>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card animate__animated animate__fadeInUp">
                        <div class="card-header text-center">
                            <h3><i class="fas fa-lock"></i> Login Portal</h3>
                        </div>
                        <div class="card-body p-5">
                            <form method="POST">
                                <div class="mb-4">
                                    <label class="form-label"><i class="fas fa-user"></i> Username / Register No</label>
                                    <input type="text" name="username" class="form-control form-control-lg" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label"><i class="fas fa-key"></i> Password</label>
                                    <input type="password" name="password" class="form-control form-control-lg" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label"><i class="fas fa-user-tag"></i> Login As</label>
                                    <select name="role" class="form-control form-control-lg">
                                        <option value="admin">👑 Admin</option>
                                        <option value="student" selected>🎓 Student</option>
                                    </select>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </form>
                            <hr class="my-4">
                            <div class="text-center">
                                <p class="mb-2">New Student? <a href="index.php?page=register" class="text-primary">Register here</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 👑 Signatures Upload Page (Only HOD Signature) -->
        <?php if($page == 'signatures' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-signature"></i> Upload Your Signature
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Admin Signature (PNG/JPG)</label>
                                    <input type="file" name="signature" class="form-control" accept="image/*" required>
                                </div>
                                <button type="submit" name="upload_signature" class="btn btn-primary w-100">
                                    <i class="fas fa-upload"></i> Upload Signature
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-signature"></i> Upload HOD Signature
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">HOD Signature (PNG/JPG)</label>
                                    <input type="file" name="hod_signature" class="form-control" accept="image/*" required>
                                </div>
                                <button type="submit" name="upload_hod_signature" class="btn btn-primary w-100">
                                    <i class="fas fa-upload"></i> Upload HOD Signature
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i> Signature Information
                        </div>
                        <div class="card-body">
                            <p>✅ Signatures will appear on all generated certificates</p>
                            <p>✅ Upload PNG images with transparent background for best results</p>
                            <p>✅ Maximum file size: 2MB</p>
                            <p>✅ Only HOD signature will appear on certificates (Principal signature removed)</p>
                            <p>✅ Absent students will NOT receive certificates</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 📤 Excel Upload Page -->
        <?php if($page == 'excel_upload' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="excel-upload-section">
                        <h3><i class="fas fa-file-excel"></i> Excel File Upload</h3>
                        <p>Upload Excel files for attendance, student lists, or other data</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-upload"></i> Upload Excel File
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Select Excel File (XLS, XLSX, CSV)</label>
                                    <input type="file" name="excel_file" class="form-control" accept=".xls,.xlsx,.csv" required>
                                    <small class="text-muted">Maximum file size: 10MB</small>
                                </div>
                                <button type="submit" name="upload_excel_file" class="btn btn-success w-100">
                                    <i class="fas fa-upload"></i> Upload Excel File
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-download"></i> Download Templates
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-3">
                                <a href="index.php?view_attendance_format=1" class="btn btn-info">
                                    <i class="fas fa-download"></i> Attendance Template
                                </a>
                                <a href="index.php?export_students=1" class="btn btn-success">
                                    <i class="fas fa-download"></i> Student List Template
                                </a>
                                <button class="btn btn-warning" onclick="createCustomTemplate()">
                                    <i class="fas fa-file-excel"></i> Create Custom Template
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-history"></i> Upload History
                        </div>
                        <div class="card-body">
                            <?php
                            $excel_files = glob("uploads/excel/*");
                            if(count($excel_files) > 0):
                            ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>File Name</th>
                                            <th>Size</th>
                                            <th>Upload Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($excel_files as $file): 
                                            $filename = basename($file);
                                            $filesize = round(filesize($file) / 1024, 2) . ' KB';
                                            $filetime = date('d M Y H:i:s', filemtime($file));
                                        ?>
                                        <tr>
                                            <td><?= $filename ?></td>
                                            <td><?= $filesize ?></td>
                                            <td><?= $filetime ?></td>
                                            <td>
                                                <a href="<?= $file ?>" download class="btn btn-sm btn-success">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button class="btn btn-sm btn-info" onclick="viewFile('<?= $file ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-center py-3">No Excel files uploaded yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function createCustomTemplate() {
                    // Create a simple Excel template
                    var csvContent = "Data 1,Data 2,Data 3,Data 4,Data 5\n";
                    csvContent += "Value 1,Value 2,Value 3,Value 4,Value 5\n";
                    csvContent += "Sample 1,Sample 2,Sample 3,Sample 4,Sample 5\n";
                    
                    var blob = new Blob([csvContent], { type: 'text/csv' });
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'custom_template.csv';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                }
                
                function viewFile(filePath) {
                    window.open(filePath, '_blank');
                }
            </script>
        <?php endif; ?>

        <!-- 👑 Admin Dashboard with Search -->
        <?php if($page == 'dashboard' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): 
            $total_events = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
            $total_students = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'")->fetch_assoc()['count'];
            $pending_students = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'pending'")->fetch_assoc()['count'];
            $total_registrations = $conn->query("SELECT COUNT(*) as count FROM registrations")->fetch_assoc()['count'];
            $total_certificates = $conn->query("SELECT COUNT(*) as count FROM certificates")->fetch_assoc()['count'];
            $upcoming = $conn->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5");
            
            // Search functionality
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            if ($search) {
                $upcoming = $conn->query("SELECT * FROM events WHERE event_date >= CURDATE() AND (title LIKE '%$search%' OR description LIKE '%$search%' OR venue LIKE '%$search%') ORDER BY event_date ASC LIMIT 5");
            }
        ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="page" value="dashboard">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Search events, students, or registrations..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn ms-2">Search</button>
                </form>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="text-white"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
                    <p class="text-white-50">Welcome back, <?= $_SESSION['username'] ?>! Here's your overview.</p>
                </div>
            </div>
            
            <div class="row mb-5">
                <div class="col-md-2 mb-3">
                    <div class="dashboard-card position-relative">
                        <h2><?= $total_events ?></h2>
                        <p class="mb-0">Total Events</p>
                        <i class="fas fa-calendar-alt stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="dashboard-card position-relative" style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);">
                        <h2><?= $total_students ?></h2>
                        <p class="mb-0">Active Students</p>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="dashboard-card position-relative" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h2><?= $pending_students ?></h2>
                        <p class="mb-0">Pending Approval</p>
                        <i class="fas fa-clock stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="dashboard-card position-relative" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                        <h2><?= $total_registrations ?></h2>
                        <p class="mb-0">Registrations</p>
                        <i class="fas fa-clipboard-list stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="dashboard-card position-relative" style="background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);">
                        <h2><?= $total_certificates ?></h2>
                        <p class="mb-0">Certificates</p>
                        <i class="fas fa-certificate stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="dashboard-card position-relative" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                        <h2><?= date('d M') ?></h2>
                        <p class="mb-0">Current Date</p>
                        <i class="fas fa-calendar stat-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-calendar"></i> Upcoming Events
                        </div>
                        <div class="card-body">
                            <?php if($upcoming->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while($event = $upcoming->fetch_assoc()): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5><?= $event['title'] ?></h5>
                                                <p class="mb-1"><?= substr($event['description'], 0, 100) ?>...</p>
                                                <small>📍 <?= $event['venue'] ?></small>
                                            </div>
                                            <span class="event-badge"><?= date('d M Y', strtotime($event['event_date'])) ?></span>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                    <p class="text-muted">🎉 No upcoming events</p>
                                    <a href="index.php?page=events" class="btn btn-primary">Create Event</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-line"></i> Quick Actions
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="index.php?page=events" class="btn btn-primary w-100 p-3">
                                        <i class="fas fa-plus-circle"></i> Create Event
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="index.php?page=students" class="btn btn-success w-100 p-3">
                                        <i class="fas fa-user-plus"></i> Add Student
                                    </a>
                                </div>
                                <?php if($pending_students > 0): ?>
                                <div class="col-6">
                                    <a href="index.php?page=pending_students" class="btn btn-warning w-100 p-3">
                                        <i class="fas fa-clock"></i> Approve (<?= $pending_students ?>)
                                    </a>
                                </div>
                                <?php endif; ?>
                                <div class="col-6">
                                    <a href="index.php?page=reports" class="btn btn-info w-100 p-3">
                                        <i class="fas fa-download"></i> Reports
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="index.php?page=certificates" class="btn btn-secondary w-100 p-3">
                                        <i class="fas fa-certificate"></i> Certificates
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="index.php?page=bulk_certificates" class="btn btn-warning w-100 p-3">
                                        <i class="fas fa-layer-group"></i> Bulk Certificates
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="index.php?page=signatures" class="btn btn-dark w-100 p-3">
                                        <i class="fas fa-signature"></i> Signatures
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="index.php?page=excel_upload" class="btn btn-success w-100 p-3">
                                        <i class="fas fa-file-excel"></i> Excel Upload
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="index.php?view_attendance_format=1" class="btn btn-info w-100 p-3">
                                        <i class="fas fa-file-excel"></i> Template
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <i class="fas fa-chart-pie"></i> System Status
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label>Event Capacity Usage</label>
                                <div class="progress">
                                    <?php 
                                    $capacity_used = $conn->query("SELECT SUM(max_participants) as total FROM events")->fetch_assoc();
                                    $percentage = $capacity_used['total'] > 0 ? 75 : 0;
                                    ?>
                                    <div class="progress-bar" style="width: <?= $percentage ?>%"><?= $percentage ?>%</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label>Attendance Rate</label>
                                <div class="progress">
                                    <?php 
                                    $attendance_rate = $conn->query("SELECT 
                                        (SELECT COUNT(*) FROM attendance WHERE status = 'present') * 100 / 
                                        NULLIF((SELECT COUNT(*) FROM attendance), 0) as rate")->fetch_assoc();
                                    $rate = round($attendance_rate['rate'] ?? 0);
                                    ?>
                                    <div class="progress-bar" style="width: <?= $rate ?>%"><?= $rate ?>%</div>
                                </div>
                            </div>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle"></i> Absent students will NOT receive certificates
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 🎓 Student Dashboard with Search -->
        <?php if($page == 'student_dashboard' && isset($_SESSION['role']) && $_SESSION['role'] == 'student'): 
            $student_id = $_SESSION['user_id'];
            $student_details = $conn->query("SELECT * FROM students WHERE id = $student_id")->fetch_assoc();
            $my_regs = $conn->query("SELECT COUNT(*) as count FROM registrations WHERE student_id = $student_id")->fetch_assoc()['count'];
            $my_certs = $conn->query("SELECT COUNT(*) as count FROM certificates WHERE student_id = $student_id")->fetch_assoc()['count'];
            $my_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE student_id = $student_id AND status = 'present'")->fetch_assoc()['count'];
            
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $upcoming_student = $conn->query("SELECT e.*, r.registration_date 
                                             FROM events e 
                                             JOIN registrations r ON e.id = r.event_id 
                                             WHERE r.student_id = $student_id 
                                             AND e.event_date >= CURDATE() 
                                             " . ($search ? "AND (e.title LIKE '%$search%' OR e.description LIKE '%$search%' OR e.venue LIKE '%$search%')" : "") . "
                                             ORDER BY e.event_date ASC LIMIT 5");
        ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="page" value="student_dashboard">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Search your events..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn ms-2">Search</button>
                </form>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card p-4">
                        <div class="d-flex align-items-center">
                            <?php if($student_details['photo'] && file_exists($student_details['photo'])): ?>
                                <img src="<?= $student_details['photo'] ?>" alt="Profile" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-right: 20px;">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-4x text-primary" style="margin-right: 20px;"></i>
                            <?php endif; ?>
                            <div>
                                <h2>Welcome back, <?= $_SESSION['username'] ?>! 👋</h2>
                                <p class="lead mb-0">Register No: <?= $_SESSION['reg_no'] ?></p>
                                <p class="mb-0">Class: <?= $student_details['class_level'] ?> - Year <?= $student_details['year_of_study'] ?></p>
                                <span class="event-badge"><i class="fas fa-certificate"></i> <?= $my_certs ?> Certificates</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="dashboard-card">
                        <h2><?= $my_regs ?></h2>
                        <p>Registered Events</p>
                        <i class="fas fa-calendar-check stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="dashboard-card" style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);">
                        <h2><?= $my_attendance ?></h2>
                        <p>Present Events</p>
                        <i class="fas fa-check-circle stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="dashboard-card" style="background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);">
                        <h2><?= $my_certs ?></h2>
                        <p>Certificates</p>
                        <i class="fas fa-certificate stat-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-calendar"></i> My Upcoming Events
                        </div>
                        <div class="card-body">
                            <?php if($upcoming_student->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while($event = $upcoming_student->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between">
                                                <h5><?= $event['title'] ?></h5>
                                                <span class="event-badge"><?= date('d M Y', strtotime($event['event_date'])) ?></span>
                                            </div>
                                            <p class="mb-1">📍 <?= $event['venue'] ?></p>
                                            <small>Registered: <?= date('d M Y', strtotime($event['registration_date'])) ?></small>
                                            <?php if($event['event_poster']): ?>
                                                <a href="<?= $event['event_poster'] ?>" target="_blank" class="btn btn-sm btn-info mt-2">
                                                    <i class="fas fa-image"></i> View Poster
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center my-5">✨ No upcoming events.</p>
                                <div class="text-center">
                                    <a href="index.php?page=available_events" class="btn btn-primary">Browse Events</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-certificate"></i> Recent Certificates
                        </div>
                        <div class="card-body">
                            <?php 
                            $recent_certs = $conn->query("SELECT c.*, e.title as event_title, e.event_poster
                                                         FROM certificates c
                                                         JOIN events e ON c.event_id = e.id
                                                         WHERE c.student_id = $student_id
                                                         ORDER BY c.issue_date DESC LIMIT 5");
                            ?>
                            <?php if($recent_certs->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while($cert = $recent_certs->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6><?= $cert['event_title'] ?></h6>
                                                    <small>Certificate No: <?= $cert['certificate_no'] ?></small>
                                                </div>
                                                <div>
                                                    <a href="index.php?download_certificate=<?= $cert['id'] ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button onclick="window.open('index.php?download_certificate=<?= $cert['id'] ?>&print=1', '_blank')" class="btn btn-sm btn-info">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center my-5">🎫 No certificates yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ⏳ Pending Students Approval with ID Card Preview and Search -->
        <?php if($page == 'pending_students' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): 
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $pending = $conn->query("SELECT * FROM students WHERE status = 'pending' " . 
                                    ($search ? "AND (name LIKE '%$search%' OR reg_no LIKE '%$search%' OR email LIKE '%$search%' OR department LIKE '%$search%')" : "") . 
                                    " ORDER BY created_at DESC");
        ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="page" value="pending_students">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Search pending students by name, reg no, email..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn ms-2">Search</button>
                </form>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-clock"></i> Pending Student Approvals
                        </div>
                        <div class="card-body">
                            <?php if($pending->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover datatable">
                                        <thead>
                                            <tr>
                                                <th>Photo</th>
                                                <th>ID Card</th>
                                                <th>Register No</th>
                                                <th>Name</th>
                                                <th>Department</th>
                                                <th>Class</th>
                                                <th>Year</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Registered On</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($student = $pending->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <?php if($student['photo'] && file_exists($student['photo'])): ?>
                                                            <img src="<?= $student['photo'] ?>" alt="Student" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; cursor: pointer;" onclick="showPhoto('<?= $student['photo'] ?>')">
                                                        <?php else: ?>
                                                            <i class="fas fa-user-circle fa-3x text-primary"></i>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($student['id_card_photo'] && file_exists($student['id_card_photo'])): ?>
                                                            <a href="#" onclick="showIdCard('<?= $student['id_card_photo'] ?>')" class="id-card-link">
                                                                <i class="fas fa-id-card fa-2x"></i>
                                                                <br>View ID Card
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">No ID Card</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><strong><?= $student['reg_no'] ?></strong></td>
                                                    <td><?= $student['name'] ?></td>
                                                    <td><?= $student['department'] ?></td>
                                                    <td><?= $student['class_level'] ?></td>
                                                    <td><?= $student['year_of_study'] ?></td>
                                                    <td><?= $student['email'] ?></td>
                                                    <td><?= $student['phone'] ?? 'N/A' ?></td>
                                                    <td><?= date('d M Y', strtotime($student['created_at'])) ?></td>
                                                    <td>
                                                        <a href="index.php?approve_student=<?= $student['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this student?')">
                                                            <i class="fas fa-check-circle"></i> Approve
                                                        </a>
                                                        <a href="index.php?reject_student=<?= $student['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this registration?')">
                                                            <i class="fas fa-times-circle"></i> Reject
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                    <h4>No Pending Approvals</h4>
                                    <p class="text-muted">All student registrations have been processed.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Photo Modal -->
            <div class="modal fade" id="photoModal" tabindex="-1">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-camera"></i> Student Photo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="modalPhoto" src="" alt="Student Photo" style="max-width: 100%; max-height: 400px; border-radius: 10px;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ID Card Modal -->
            <div class="modal fade" id="idCardModal" tabindex="-1">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-id-card"></i> Student ID Card</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="modalIdCard" src="" alt="ID Card" style="max-width: 100%; max-height: 400px; border-radius: 10px;">
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function showPhoto(imagePath) {
                    document.getElementById('modalPhoto').src = imagePath;
                    $('#photoModal').modal('show');
                }
                
                function showIdCard(imagePath) {
                    document.getElementById('modalIdCard').src = imagePath;
                    $('#idCardModal').modal('show');
                    return false;
                }
            </script>
        <?php endif; ?>

        <!-- 👥 Student List View with ID Card Preview and Search -->
        <?php if($page == 'students' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): 
            
            // Handle student status update
            if(isset($_GET['toggle_status'])) {
                $student_id = intval($_GET['toggle_status']);
                $new_status = $conn->real_escape_string($_GET['status']);
                $conn->query("UPDATE students SET status = '$new_status' WHERE id = $student_id");
                $success = "✅ Student status updated to $new_status!";
                header('Location: index.php?page=students');
                exit;
            }
            
            // Handle student deletion
            if(isset($_GET['delete_student'])) {
                $student_id = intval($_GET['delete_student']);
                
                // Check if student has registrations
                $check = $conn->query("SELECT COUNT(*) as count FROM registrations WHERE student_id = $student_id")->fetch_assoc()['count'];
                if($check > 0) {
                    $error = "❌ Cannot delete student with existing registrations!";
                } else {
                    // Delete student photos if exists
                    $student = $conn->query("SELECT photo, id_card_photo FROM students WHERE id = $student_id")->fetch_assoc();
                    if($student['photo'] && file_exists($student['photo'])) {
                        unlink($student['photo']);
                    }
                    if($student['id_card_photo'] && file_exists($student['id_card_photo'])) {
                        unlink($student['id_card_photo']);
                    }
                    $conn->query("DELETE FROM students WHERE id = $student_id");
                    $success = "✅ Student deleted successfully!";
                }
                header('Location: index.php?page=students');
                exit;
            }
            
            // Handle bulk action
            if(isset($_POST['bulk_action'])) {
                $action = $_POST['bulk_action_type'];
                $selected = $_POST['selected_students'] ?? [];
                
                if(!empty($selected)) {
                    $ids = implode(',', array_map('intval', $selected));
                    
                    if($action == 'activate') {
                        $conn->query("UPDATE students SET status = 'active' WHERE id IN ($ids)");
                        $success = "✅ Selected students activated successfully!";
                    } elseif($action == 'block') {
                        $conn->query("UPDATE students SET status = 'blocked' WHERE id IN ($ids)");
                        $success = "✅ Selected students blocked successfully!";
                    } elseif($action == 'delete') {
                        // Check if any have registrations
                        $check = $conn->query("SELECT COUNT(*) as count FROM registrations WHERE student_id IN ($ids)")->fetch_assoc()['count'];
                        if($check == 0) {
                            // Delete photos
                            $students = $conn->query("SELECT photo, id_card_photo FROM students WHERE id IN ($ids)");
                            while($s = $students->fetch_assoc()) {
                                if($s['photo'] && file_exists($s['photo'])) {
                                    unlink($s['photo']);
                                }
                                if($s['id_card_photo'] && file_exists($s['id_card_photo'])) {
                                    unlink($s['id_card_photo']);
                                }
                            }
                            $conn->query("DELETE FROM students WHERE id IN ($ids)");
                            $success = "✅ Selected students deleted successfully!";
                        } else {
                            $error = "❌ Cannot delete students with existing registrations!";
                        }
                    }
                } else {
                    $error = "❌ No students selected!";
                }
            }
            
            // Get filter parameters
            $dept_filter = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';
            $status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
            $class_filter = isset($_GET['class_level']) ? $conn->real_escape_string($_GET['class_level']) : '';
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            
            // Pagination
            $page_no = isset($_GET['pageno']) ? intval($_GET['pageno']) : 1;
            $records_per_page = 20;
            $offset = ($page_no - 1) * $records_per_page;
            
            // Build query with filters
            $count_query = "SELECT COUNT(*) as total FROM students WHERE 1=1";
            $query = "SELECT s.*, 
                      COUNT(DISTINCT r.id) as events_count,
                      COUNT(DISTINCT a.id) as attendance_count,
                      COUNT(DISTINCT c.id) as certificate_count,
                      MAX(r.registration_date) as last_registration
                      FROM students s
                      LEFT JOIN registrations r ON s.id = r.student_id
                      LEFT JOIN attendance a ON s.id = a.student_id
                      LEFT JOIN certificates c ON s.id = c.student_id
                      WHERE 1=1";
            
            if($dept_filter) {
                $query .= " AND s.department = '$dept_filter'";
                $count_query .= " AND department = '$dept_filter'";
            }
            
            if($status_filter) {
                $query .= " AND s.status = '$status_filter'";
                $count_query .= " AND status = '$status_filter'";
            }
            
            if($class_filter) {
                $query .= " AND s.class_level = '$class_filter'";
                $count_query .= " AND class_level = '$class_filter'";
            }
            
            if($search) {
                $query .= " AND (s.name LIKE '%$search%' OR s.reg_no LIKE '%$search%' OR s.email LIKE '%$search%' OR s.phone LIKE '%$search%')";
                $count_query .= " AND (name LIKE '%$search%' OR reg_no LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
            }
            
            $query .= " GROUP BY s.id ORDER BY s.created_at DESC LIMIT $offset, $records_per_page";
            
            // Get total records for pagination
            $total_records = $conn->query($count_query)->fetch_assoc()['total'];
            $total_pages = ceil($total_records / $records_per_page);
            
            $students = $conn->query($query);
            
            // Get department list for filter
            $departments = $conn->query("SELECT DISTINCT department FROM students ORDER BY department");
            $class_levels = $conn->query("SELECT DISTINCT class_level FROM students ORDER BY class_level");
            
            // Get statistics
            $stats = [
                'total' => $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'],
                'active' => $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'")->fetch_assoc()['count'],
                'pending' => $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'pending'")->fetch_assoc()['count'],
                'blocked' => $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'blocked'")->fetch_assoc()['count']
            ];
        ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" class="row g-2">
                    <input type="hidden" name="page" value="students">
                    <div class="col-md-3">
                        <select name="department" class="form-control">
                            <option value="">All Departments</option>
                            <?php 
                            mysqli_data_seek($departments, 0);
                            while($dept = $departments->fetch_assoc()): 
                            ?>
                                <option value="<?= htmlspecialchars($dept['department']) ?>" <?= $dept_filter == $dept['department'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['department']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="class_level" class="form-control">
                            <option value="">All Classes</option>
                            <?php 
                            mysqli_data_seek($class_levels, 0);
                            while($class = $class_levels->fetch_assoc()): 
                            ?>
                                <option value="<?= htmlspecialchars($class['class_level']) ?>" <?= $class_filter == $class['class_level'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_level']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="blocked" <?= $status_filter == 'blocked' ? 'selected' : '' ?>>Blocked</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="🔍 Search..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="dashboard-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <h2><?= $stats['total'] ?></h2>
                                <p class="mb-0">Total Students</p>
                                <i class="fas fa-users stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="dashboard-card" style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);">
                                <h2><?= $stats['active'] ?></h2>
                                <p class="mb-0">Active Students</p>
                                <i class="fas fa-check-circle stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="dashboard-card" style="background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);">
                                <h2><?= $stats['pending'] ?></h2>
                                <p class="mb-0">Pending Approval</p>
                                <i class="fas fa-clock stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="dashboard-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                <h2><?= $stats['blocked'] ?></h2>
                                <p class="mb-0">Blocked Students</p>
                                <i class="fas fa-ban stat-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Main Card -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-users"></i> Student Management
                                <span class="badge bg-primary ms-2">Total: <?= $total_records ?></span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                    <i class="fas fa-user-plus"></i> Add Student
                                </button>
                                <button type="button" class="btn btn-success me-2" onclick="exportStudents()">
                                    <i class="fas fa-file-excel"></i> Export
                                </button>
                                <button type="button" class="btn btn-info" onclick="printStudentList()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                        
                        <!-- Bulk Actions -->
                        <div class="card-body">
                            <form method="POST" id="bulkActionForm" onsubmit="return confirmBulkAction()">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <select name="bulk_action_type" class="form-control">
                                            <option value="">Bulk Actions</option>
                                            <option value="activate">Activate Selected</option>
                                            <option value="block">Block Selected</option>
                                            <option value="delete">Delete Selected</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" name="bulk_action" class="btn btn-warning">Apply</button>
                                    </div>
                                    <div class="col-md-7 text-end">
                                        <button type="button" class="btn btn-sm btn-link" onclick="selectAll()">Select All</button>
                                        <button type="button" class="btn btn-sm btn-link" onclick="deselectAll()">Deselect All</button>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover" id="studentsTable">
                                        <thead>
                                            <tr>
                                                <th width="30">
                                                    <input type="checkbox" id="selectAllCheckbox" onclick="toggleAll(this)">
                                                </th>
                                                <th>Photo</th>
                                                <th>ID Card</th>
                                                <th>Register No</th>
                                                <th>Name</th>
                                                <th>Department</th>
                                                <th>Class</th>
                                                <th>Year</th>
                                                <th>Contact</th>
                                                <th>Events</th>
                                                <th>Last Activity</th>
                                                <th>Status</th>
                                                <th>Joined</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if($students && $students->num_rows > 0): ?>
                                                <?php while($student = $students->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" name="selected_students[]" value="<?= $student['id'] ?>" class="student-checkbox">
                                                        </td>
                                                        <td>
                                                            <?php if($student['photo'] && file_exists($student['photo'])): ?>
                                                                <img src="<?= $student['photo'] ?>" alt="Student" class="student-photo" onclick="showPhoto('<?= $student['photo'] ?>')">
                                                            <?php else: ?>
                                                                <div class="student-initial">
                                                                    <?= strtoupper(substr($student['name'], 0, 1)) ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if($student['id_card_photo'] && file_exists($student['id_card_photo'])): ?>
                                                                <a href="#" onclick="showIdCard('<?= $student['id_card_photo'] ?>')" class="id-card-link">
                                                                    <i class="fas fa-id-card fa-2x"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-muted">No ID</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><strong><?= htmlspecialchars($student['reg_no']) ?></strong></td>
                                                        <td>
                                                            <?= htmlspecialchars($student['name']) ?>
                                                            <?php if($student['status'] == 'pending'): ?>
                                                                <br><small class="text-warning">⏳ Pending</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($student['department']) ?></td>
                                                        <td><?= htmlspecialchars($student['class_level']) ?></td>
                                                        <td><?= $student['year_of_study'] ?></td>
                                                        <td>
                                                            <i class="fas fa-envelope text-muted small"></i> <?= htmlspecialchars($student['email']) ?><br>
                                                            <i class="fas fa-phone text-muted small"></i> <?= htmlspecialchars($student['phone'] ?? 'N/A') ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info" title="Events"><?= $student['events_count'] ?? 0 ?> 📋</span>
                                                            <span class="badge bg-success" title="Attendance"><?= $student['attendance_count'] ?? 0 ?> ✅</span>
                                                            <span class="badge bg-warning" title="Certificates"><?= $student['certificate_count'] ?? 0 ?> 🎫</span>
                                                        </td>
                                                        <td>
                                                            <?php if($student['last_registration']): ?>
                                                                <small><?= date('d M Y', strtotime($student['last_registration'])) ?></small>
                                                            <?php else: ?>
                                                                <small class="text-muted">No activity</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $status_class = '';
                                                            $status_icon = '';
                                                            switch($student['status']) {
                                                                case 'active':
                                                                    $status_class = 'success';
                                                                    $status_icon = '✅';
                                                                    break;
                                                                case 'pending':
                                                                    $status_class = 'warning';
                                                                    $status_icon = '⏳';
                                                                    break;
                                                                case 'blocked':
                                                                    $status_class = 'danger';
                                                                    $status_icon = '🚫';
                                                                    break;
                                                                default:
                                                                    $status_class = 'secondary';
                                                                    $status_icon = '❓';
                                                            }
                                                            ?>
                                                            <span class="badge bg-<?= $status_class ?>"><?= $status_icon ?> <?= ucfirst($student['status']) ?></span>
                                                        </td>
                                                        <td>
                                                            <small><?= date('d M Y', strtotime($student['created_at'])) ?></small>
                                                            <br><small class="text-muted"><?= date('h:i A', strtotime($student['created_at'])) ?></small>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group-vertical btn-group-sm">
                                                                <button type="button" class="btn btn-info btn-sm" onclick="viewStudent(<?= $student['id'] ?>)" title="View Details">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                
                                                                <?php if($student['status'] == 'pending'): ?>
                                                                    <a href="index.php?approve_student=<?= $student['id'] ?>" class="btn btn-success btn-sm" title="Approve" onclick="return confirm('Approve this student?')">
                                                                        <i class="fas fa-check-circle"></i>
                                                                    </a>
                                                                    <a href="index.php?reject_student=<?= $student['id'] ?>" class="btn btn-danger btn-sm" title="Reject" onclick="return confirm('Reject this registration?')">
                                                                        <i class="fas fa-times-circle"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                                
                                                                <?php if($student['status'] == 'active'): ?>
                                                                    <a href="index.php?page=students&toggle_status=<?= $student['id'] ?>&status=blocked" class="btn btn-warning btn-sm" title="Block" onclick="return confirm('Block this student?')">
                                                                        <i class="fas fa-ban"></i>
                                                                    </a>
                                                                <?php elseif($student['status'] == 'blocked'): ?>
                                                                    <a href="index.php?page=students&toggle_status=<?= $student['id'] ?>&status=active" class="btn btn-success btn-sm" title="Activate" onclick="return confirm('Activate this student?')">
                                                                        <i class="fas fa-check"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                                
                                                                <?php if(($student['events_count'] ?? 0) == 0): ?>
                                                                    <a href="index.php?page=students&delete_student=<?= $student['id'] ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('⚠️ Permanently delete this student?\nThis action cannot be undone!')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="14" class="text-center py-5">
                                                        <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                                        <h4>No Students Found</h4>
                                                        <p class="text-muted"><?= $search ? 'Try adjusting your search filters' : 'Start by adding some students!' ?></p>
                                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                                            <i class="fas fa-user-plus"></i> Add Your First Student
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                            
                            <!-- Pagination -->
                            <?php if($total_pages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-3">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= $page_no <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=students&pageno=<?= $page_no-1 ?>&department=<?= urlencode($dept_filter) ?>&status=<?= urlencode($status_filter) ?>&class_level=<?= urlencode($class_filter) ?>&search=<?= urlencode($search) ?>">Previous</a>
                                        </li>
                                        
                                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                            <?php if($i >= $page_no - 2 && $i <= $page_no + 2): ?>
                                                <li class="page-item <?= $i == $page_no ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=students&pageno=<?= $i ?>&department=<?= urlencode($dept_filter) ?>&status=<?= urlencode($status_filter) ?>&class_level=<?= urlencode($class_filter) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                                </li>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?= $page_no >= $total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=students&pageno=<?= $page_no+1 ?>&department=<?= urlencode($dept_filter) ?>&status=<?= urlencode($status_filter) ?>&class_level=<?= urlencode($class_filter) ?>&search=<?= urlencode($search) ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Details Modal -->
            <div class="modal fade" id="studentDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-user-graduate"></i> Student Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="studentDetailsContent">
                            <div class="text-center">
                                <div class="loader"></div>
                                <p class="mt-2">Loading student details...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ➕ Add Student Modal with ID Card and Class/Level -->
            <div class="modal fade" id="addStudentModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New Student</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" enctype="multipart/form-data" onsubmit="return validateStudentForm()">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Register Number <span class="text-danger">*</span></label>
                                        <input type="text" name="reg_no" class="form-control" required pattern="[0-9]{2}[A-Za-z]{3}[0-9]{6}" title="2 digits + 3 letters + 6 digits">
                                        <small class="text-muted">e.g., 11UCA222222 (2 digits + 3 letters + 6 digits)</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Department <span class="text-danger">*</span></label>
                                        <select name="department" class="form-control" required>
                                            <option value="">Select Department</option>
                                            <option value="Computer Science">Computer Science</option>
                                            <option value="Information Technology">Information Technology</option>
                                            <option value="Electronics">Electronics</option>
                                            <option value="Mechanical">Mechanical</option>
                                            <option value="Civil">Civil</option>
                                            <option value="Electrical">Electrical</option>
                                            <option value="Mathematics">Mathematics</option>
                                            <option value="Physics">Physics</option>
                                            <option value="Chemistry">Chemistry</option>
                                            <option value="English">English</option>
                                            <option value="Tamil">Tamil</option>
                                            <option value="Statistics">Statistics</option>
                                            <option value="Computer Application (BCA)">Computer Application (BCA)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Class Level <span class="text-danger">*</span></label>
                                        <select name="class_level" class="form-control" required>
                                            <option value="">Select Class</option>
                                            <option value="UG">UG (Under Graduate)</option>
                                            <option value="PG">PG (Post Graduate)</option>
                                            <option value="Diploma">Diploma</option>
                                            <option value="Certificate">Certificate</option>
                                            <option value="Research">Research</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Year of Study <span class="text-danger">*</span></label>
                                        <select name="year_of_study" class="form-control" required>
                                            <option value="">Select Year</option>
                                            <option value="1">1st Year</option>
                                            <option value="2">2nd Year</option>
                                            <option value="3">3rd Year</option>
                                            <option value="4">4th Year</option>
                                            <option value="5">5th Year</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" pattern="[0-9]{10}" title="10 digit phone number">
                                        <small class="text-muted">10 digit mobile number</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" placeholder="Leave blank for default (student123)">
                                        <small class="text-muted">Default: student123</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Passport Photo</label>
                                        <input type="file" name="photo" class="form-control" accept="image/*" onchange="previewImage(this, 'imagePreview')">
                                        <small class="text-muted">JPG, PNG, Max 2MB</small>
                                        <div id="imagePreview" class="mt-2"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ID Card Photo</label>
                                        <input type="file" name="id_card" class="form-control" accept="image/*" onchange="previewImage(this, 'idCardPreview')">
                                        <small class="text-muted">JPG, PNG, Max 5MB</small>
                                        <div id="idCardPreview" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="add_student" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Add Student
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Photo and ID Card Modals -->
            <div class="modal fade" id="photoModal" tabindex="-1">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-camera"></i> Student Photo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="modalPhoto" src="" alt="Student Photo" class="student-id-card">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="idCardModal" tabindex="-1">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-id-card"></i> Student ID Card</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="modalIdCard" src="" alt="ID Card" class="student-id-card">
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function showPhoto(imagePath) {
                    document.getElementById('modalPhoto').src = imagePath;
                    $('#photoModal').modal('show');
                }
                
                function showIdCard(imagePath) {
                    document.getElementById('modalIdCard').src = imagePath;
                    $('#idCardModal').modal('show');
                    return false;
                }
                
                // Select All functionality
                function selectAll() {
                    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = true);
                    document.getElementById('selectAllCheckbox').checked = true;
                }
                
                function deselectAll() {
                    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
                    document.getElementById('selectAllCheckbox').checked = false;
                }
                
                function toggleAll(source) {
                    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = source.checked);
                }
                
                function confirmBulkAction() {
                    var action = document.querySelector('select[name="bulk_action_type"]').value;
                    var selected = document.querySelectorAll('.student-checkbox:checked').length;
                    
                    if(!action) {
                        alert('Please select an action!');
                        return false;
                    }
                    
                    if(selected == 0) {
                        alert('Please select at least one student!');
                        return false;
                    }
                    
                    return confirm('Are you sure you want to ' + action + ' ' + selected + ' student(s)?');
                }
                
                function viewStudent(studentId) {
                    $('#studentDetailsModal').modal('show');
                    
                    // Fetch student details via AJAX
                    $.ajax({
                        url: 'index.php',
                        method: 'GET',
                        data: {
                            page: 'student_details',
                            id: studentId,
                            ajax: 1
                        },
                        success: function(response) {
                            $('#studentDetailsContent').html(response);
                        },
                        error: function() {
                            $('#studentDetailsContent').html('<div class="alert alert-danger">Failed to load student details</div>');
                        }
                    });
                }
                
                function exportStudents() {
                    var department = $('select[name="department"]').val();
                    var status = $('select[name="status"]').val();
                    var classLevel = $('select[name="class_level"]').val();
                    var search = $('input[name="search"]').val();
                    
                    var url = 'index.php?export_students=1';
                    if(department) url += '&department=' + encodeURIComponent(department);
                    if(status) url += '&status=' + encodeURIComponent(status);
                    if(classLevel) url += '&class_level=' + encodeURIComponent(classLevel);
                    if(search) url += '&search=' + encodeURIComponent(search);
                    
                    window.location.href = url;
                }
                
                function printStudentList() {
                    var printWindow = window.open('', '_blank');
                    var content = document.getElementById('studentsTable').cloneNode(true);
                    
                    // Remove action buttons for printing
                    content.querySelectorAll('td:last-child, th:last-child').forEach(el => el.remove());
                    content.querySelectorAll('td:first-child, th:first-child').forEach(el => el.remove()); // Remove checkboxes
                    
                    printWindow.document.write(`
                        <html>
                        <head>
                            <title>Student List</title>
                            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                            <style>
                                body { padding: 20px; }
                                @media print {
                                    .no-print { display: none; }
                                }
                            </style>
                        </head>
                        <body>
                            <h2 class="text-center mb-4">Student List</h2>
                            <p>Generated on: ${new Date().toLocaleString()}</p>
                            ${content.outerHTML}
                        </body>
                        </html>
                    `);
                    
                    printWindow.document.close();
                    printWindow.print();
                }
                
                function previewImage(input, previewId) {
                    var preview = document.getElementById(previewId);
                    preview.innerHTML = '';
                    
                    if (input.files && input.files[0]) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            var img = document.createElement('img');
                            img.src = e.target.result;
                            img.style.maxWidth = '200px';
                            img.style.maxHeight = '200px';
                            img.style.borderRadius = '10px';
                            preview.appendChild(img);
                        }
                        reader.readAsDataURL(input.files[0]);
                    }
                }
                
                function validateStudentForm() {
                    var reg_no = document.querySelector('input[name="reg_no"]').value;
                    var email = document.querySelector('input[name="email"]').value;
                    var phone = document.querySelector('input[name="phone"]').value;
                    var classLevel = document.querySelector('select[name="class_level"]').value;
                    var yearOfStudy = document.querySelector('select[name="year_of_study"]').value;
                    
                    // Register number validation (2 digits + 3 letters + 6 digits)
                    if(!/^[0-9]{2}[A-Za-z]{3}[0-9]{6}$/.test(reg_no)) {
                        alert('Register number must be 2 digits + 3 letters + 6 digits! Example: 11UCA222222');
                        return false;
                    }
                    
                    // Email validation
                    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        alert('Please enter a valid email address!');
                        return false;
                    }
                    
                    // Phone validation (if provided)
                    if(phone && !/^\d{10}$/.test(phone)) {
                        alert('Phone number should be 10 digits!');
                        return false;
                    }
                    
                    // Class level validation
                    if(!classLevel) {
                        alert('Please select class level!');
                        return false;
                    }
                    
                    // Year of study validation
                    if(!yearOfStudy) {
                        alert('Please select year of study!');
                        return false;
                    }
                    
                    return true;
                }
            </script>
        <?php endif; ?>

        <!-- 👤 Student Details AJAX Handler with ID Card and Class/Level -->
        <?php 
        if($page == 'student_details' && isset($_GET['id']) && isset($_GET['ajax'])) {
            $student_id = intval($_GET['id']);
            
            $student = $conn->query("SELECT s.*, 
                                    COUNT(DISTINCT r.id) as events_count,
                                    COUNT(DISTINCT a.id) as attendance_count,
                                    COUNT(DISTINCT c.id) as certificate_count
                                    FROM students s
                                    LEFT JOIN registrations r ON s.id = r.student_id
                                    LEFT JOIN attendance a ON s.id = a.student_id
                                    LEFT JOIN certificates c ON s.id = c.student_id
                                    WHERE s.id = $student_id
                                    GROUP BY s.id")->fetch_assoc();
            
            if($student) {
                $recent_events = $conn->query("SELECT e.title, e.event_date, e.event_poster, r.registration_date, 
                                              r.attendance_status, r.certificate_issued
                                              FROM events e
                                              JOIN registrations r ON e.id = r.event_id
                                              WHERE r.student_id = $student_id
                                              ORDER BY e.event_date DESC
                                              LIMIT 5");
                ?>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <?php if($student['photo'] && file_exists($student['photo'])): ?>
                                <img src="<?= $student['photo'] ?>" alt="Student" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #764ba2; cursor: pointer;" onclick="showPhoto('<?= $student['photo'] ?>')">
                            <?php else: ?>
                                <div style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; margin: 0 auto;">
                                    <?= strtoupper(substr($student['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($student['id_card_photo'] && file_exists($student['id_card_photo'])): ?>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-info" onclick="showIdCard('<?= $student['id_card_photo'] ?>')">
                                        <i class="fas fa-id-card"></i> View ID Card
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <h4 class="mt-3"><?= htmlspecialchars($student['name']) ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($student['reg_no']) ?></p>
                            <p class="text-muted"><?= $student['class_level'] ?> - Year <?= $student['year_of_study'] ?></p>
                            
                            <div class="mt-3">
                                <span class="badge bg-<?= $student['status'] == 'active' ? 'success' : ($student['status'] == 'pending' ? 'warning' : 'danger') ?> p-2">
                                    <?= ucfirst($student['status']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light p-3">
                                        <h6>📧 Email</h6>
                                        <p><?= htmlspecialchars($student['email']) ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light p-3">
                                        <h6>📱 Phone</h6>
                                        <p><?= htmlspecialchars($student['phone'] ?? 'Not provided') ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light p-3">
                                        <h6>🏛️ Department</h6>
                                        <p><?= htmlspecialchars($student['department']) ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light p-3">
                                        <h6>📅 Joined</h6>
                                        <p><?= date('d M Y', strtotime($student['created_at'])) ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="card text-center p-3">
                                        <h3><?= $student['events_count'] ?? 0 ?></h3>
                                        <small>Events</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-center p-3">
                                        <h3><?= $student['attendance_count'] ?? 0 ?></h3>
                                        <small>Attendance</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-center p-3">
                                        <h3><?= $student['certificate_count'] ?? 0 ?></h3>
                                        <small>Certificates</small>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if($recent_events && $recent_events->num_rows > 0): ?>
                                <div class="mt-4">
                                    <h6>Recent Events</h6>
                                    <ul class="list-group">
                                        <?php while($event = $recent_events->fetch_assoc()): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <?= htmlspecialchars($event['title']) ?>
                                                    <?php if($event['event_poster']): ?>
                                                        <br><small><a href="<?= $event['event_poster'] ?>" target="_blank">View Poster</a></small>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="badge bg-<?= $event['attendance_status'] == 'present' ? 'success' : 'secondary' ?>">
                                                    <?= $event['attendance_status'] ?>
                                                </span>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <script>
                    function showPhoto(imagePath) {
                        var modal = document.createElement('div');
                        modal.style.position = 'fixed';
                        modal.style.top = '0';
                        modal.style.left = '0';
                        modal.style.width = '100%';
                        modal.style.height = '100%';
                        modal.style.backgroundColor = 'rgba(0,0,0,0.8)';
                        modal.style.display = 'flex';
                        modal.style.justifyContent = 'center';
                        modal.style.alignItems = 'center';
                        modal.style.zIndex = '9999';
                        modal.onclick = function() { document.body.removeChild(modal); };
                        
                        var img = document.createElement('img');
                        img.src = imagePath;
                        img.style.maxWidth = '90%';
                        img.style.maxHeight = '90%';
                        img.style.borderRadius = '10px';
                        
                        modal.appendChild(img);
                        document.body.appendChild(modal);
                    }
                    
                    function showIdCard(imagePath) {
                        var modal = document.createElement('div');
                        modal.style.position = 'fixed';
                        modal.style.top = '0';
                        modal.style.left = '0';
                        modal.style.width = '100%';
                        modal.style.height = '100%';
                        modal.style.backgroundColor = 'rgba(0,0,0,0.8)';
                        modal.style.display = 'flex';
                        modal.style.justifyContent = 'center';
                        modal.style.alignItems = 'center';
                        modal.style.zIndex = '9999';
                        modal.onclick = function() { document.body.removeChild(modal); };
                        
                        var img = document.createElement('img');
                        img.src = imagePath;
                        img.style.maxWidth = '90%';
                        img.style.maxHeight = '90%';
                        img.style.borderRadius = '10px';
                        
                        modal.appendChild(img);
                        document.body.appendChild(modal);
                    }
                </script>
                <?php
            } else {
                echo '<div class="alert alert-danger">Student not found!</div>';
            }
            exit;
        }
        ?>

        <!-- 📅 Event Management with File Uploads and Search -->
        <?php if($page == 'events' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): 
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $events = $conn->query("SELECT e.*, COUNT(r.id) as reg_count,
                                   COUNT(DISTINCT c.id) as cert_count
                                   FROM events e 
                                   LEFT JOIN registrations r ON e.id = r.event_id 
                                   LEFT JOIN certificates c ON e.id = c.event_id
                                   WHERE 1=1 " . 
                                   ($search ? "AND (e.title LIKE '%$search%' OR e.description LIKE '%$search%' OR e.venue LIKE '%$search%')" : "") . "
                                   GROUP BY e.id 
                                   ORDER BY e.event_date DESC");
        ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="page" value="events">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Search events by title, description, venue..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn ms-2">Search</button>
                </form>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-calendar-alt"></i> Event Management</span>
                            <div>
                                <button type="button" class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#addEventModal">
                                    <i class="fas fa-plus"></i> Add Event
                                </button>
                                <a href="index.php?export_report=1&type=all" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Export All
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Date</th>
                                            <th>Venue</th>
                                            <th>Registrations</th>
                                            <th>Attendance</th>
                                            <th>Certificates</th>
                                            <th>Poster</th>
                                            <th>Schedule</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($event = $events->fetch_assoc()): 
                                            $attendance_count = $conn->query("SELECT COUNT(DISTINCT student_id) as count 
                                                                            FROM attendance WHERE event_id = {$event['id']} 
                                                                            AND status = 'present'")->fetch_assoc()['count'];
                                        ?>
                                            <tr>
                                                <td>#<?= $event['id'] ?></td>
                                                <td><strong><?= $event['title'] ?></strong></td>
                                                <td><?= date('d M Y', strtotime($event['event_date'])) ?></td>
                                                <td><?= $event['venue'] ?></td>
                                                <td><span class="event-badge"><?= $event['reg_count'] ?> / <?= $event['max_participants'] ?></span></td>
                                                <td><span class="badge bg-info"><?= $attendance_count ?> Present</span></td>
                                                <td><span class="badge bg-warning"><?= $event['cert_count'] ?> Issued</span></td>
                                                <td>
                                                    <?php if($event['event_poster']): ?>
                                                        <a href="<?= $event['event_poster'] ?>" target="_blank" class="btn btn-sm btn-info">
                                                            <i class="fas fa-image"></i> View
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($event['event_schedule']): ?>
                                                        <a href="<?= $event['event_schedule'] ?>" target="_blank" class="btn btn-sm btn-info">
                                                            <i class="fas fa-file-pdf"></i> View
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($event['event_date'] > date('Y-m-d')): ?>
                                                        <span class="badge bg-success">Upcoming</span>
                                                    <?php elseif($event['event_date'] == date('Y-m-d')): ?>
                                                        <span class="badge bg-warning">Today</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Completed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="index.php?page=attendance&event_id=<?= $event['id'] ?>" class="btn btn-sm btn-info" title="Mark Attendance">
                                                            <i class="fas fa-check-circle"></i>
                                                        </a>
                                                        <a href="index.php?page=event_details&id=<?= $event['id'] ?>" class="btn btn-sm btn-primary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editEventModal<?= $event['id'] ?>" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="index.php?export_report=1&type=event&id=<?= $event['id'] ?>" class="btn btn-sm btn-success" title="Download Report">
                                                            <i class="fas fa-file-excel"></i>
                                                        </a>
                                                        <a href="index.php?bulk_certificates&event_id=<?= $event['id'] ?>" class="btn btn-sm btn-warning" title="Generate Bulk Certificates">
                                                            <i class="fas fa-layer-group"></i>
                                                        </a>
                                                        <a href="index.php?delete_event=<?= $event['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this event?')" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Edit Event Modal -->
                                            <div class="modal fade" id="editEventModal<?= $event['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Event</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-12 mb-3">
                                                                        <label>Event Title *</label>
                                                                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($event['title']) ?>" required>
                                                                    </div>
                                                                    <div class="col-md-12 mb-3">
                                                                        <label>Description *</label>
                                                                        <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($event['description']) ?></textarea>
                                                                    </div>
                                                                    <div class="col-md-6 mb-3">
                                                                        <label>Event Date *</label>
                                                                        <input type="date" name="event_date" class="form-control" value="<?= $event['event_date'] ?>" required>
                                                                    </div>
                                                                    <div class="col-md-6 mb-3">
                                                                        <label>Venue *</label>
                                                                        <input type="text" name="venue" class="form-control" value="<?= htmlspecialchars($event['venue']) ?>" required>
                                                                    </div>
                                                                    <div class="col-md-6 mb-3">
                                                                        <label>Max Participants</label>
                                                                        <input type="number" name="max_participants" class="form-control" value="<?= $event['max_participants'] ?>">
                                                                    </div>
                                                                    <div class="col-md-12 mb-3">
                                                                        <label>Event Notes</label>
                                                                        <textarea name="event_notes" class="form-control" rows="2"><?= htmlspecialchars($event['event_notes'] ?? '') ?></textarea>
                                                                    </div>
                                                                    <div class="col-md-6 mb-3">
                                                                        <label>Event Poster (Image)</label>
                                                                        <input type="file" name="event_poster" class="form-control" accept="image/*">
                                                                        <?php if($event['event_poster']): ?>
                                                                            <small>Current: <a href="<?= $event['event_poster'] ?>" target="_blank">View Poster</a></small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="col-md-6 mb-3">
                                                                        <label>Event Schedule (PDF/DOC)</label>
                                                                        <input type="file" name="event_schedule" class="form-control" accept=".pdf,.doc,.docx">
                                                                        <?php if($event['event_schedule']): ?>
                                                                            <small>Current: <a href="<?= $event['event_schedule'] ?>" target="_blank">View Schedule</a></small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="update_event" class="btn btn-primary">Update Event</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ➕ Add Event Modal with File Uploads -->
            <div class="modal fade" id="addEventModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Create New Event</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label>Event Title *</label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label>Description *</label>
                                        <textarea name="description" class="form-control" rows="3" required></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Event Date *</label>
                                        <input type="date" name="event_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Venue *</label>
                                        <input type="text" name="venue" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Max Participants</label>
                                        <input type="number" name="max_participants" class="form-control" value="100">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label>Event Notes</label>
                                        <textarea name="event_notes" class="form-control" rows="2" placeholder="Additional information about the event..."></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Event Poster (Image)</label>
                                        <input type="file" name="event_poster" class="form-control" accept="image/*">
                                        <small class="text-muted">Upload event poster/banner (JPG, PNG, Max 5MB)</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Event Schedule (PDF/DOC)</label>
                                        <input type="file" name="event_schedule" class="form-control" accept=".pdf,.doc,.docx">
                                        <small class="text-muted">Upload event schedule/timetable (PDF, DOC, Max 10MB)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="add_event" class="btn btn-primary">Create Event</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 📊 Event Details Page with Print Option -->
        <?php if($page == 'event_details' && isset($_GET['id']) && isset($_SESSION['role'])): 
            $event_id = intval($_GET['id']);
            $event = $conn->query("SELECT e.*, a.full_name as organizer 
                                  FROM events e 
                                  LEFT JOIN admin a ON e.created_by = a.id 
                                  WHERE e.id = $event_id")->fetch_assoc();
            
            if(!$event) {
                echo '<div class="alert alert-danger">Event not found!</div>';
            } else {
                $registrations = $conn->query("SELECT COUNT(*) as count FROM registrations WHERE event_id = $event_id")->fetch_assoc()['count'];
                $attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE event_id = $event_id AND status = 'present'")->fetch_assoc()['count'];
                $certificates = $conn->query("SELECT COUNT(*) as count FROM certificates WHERE event_id = $event_id")->fetch_assoc()['count'];
        ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button onclick="window.print()" class="btn btn-info ms-2">
                    <i class="fas fa-print"></i> Print Details
                </button>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-calendar-alt"></i> Event Details: <?= htmlspecialchars($event['title']) ?>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="event-detail-card">
                                        <h4><?= htmlspecialchars($event['title']) ?></h4>
                                        <p><strong>📅 Date:</strong> <?= date('l, d F Y', strtotime($event['event_date'])) ?></p>
                                        <p><strong>📍 Venue:</strong> <?= htmlspecialchars($event['venue']) ?></p>
                                        <p><strong>📝 Description:</strong></p>
                                        <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                                        
                                        <?php if($event['event_notes']): ?>
                                            <p><strong>📋 Notes:</strong></p>
                                            <p><?= nl2br(htmlspecialchars($event['event_notes'])) ?></p>
                                        <?php endif; ?>
                                        
                                        <p><strong>👤 Organized by:</strong> <?= htmlspecialchars($event['organizer'] ?? 'Unknown') ?></p>
                                        
                                        <hr>
                                        
                                        <h5>Statistics</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="card text-center p-3">
                                                    <h3><?= $registrations ?></h3>
                                                    <small>Registrations</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card text-center p-3">
                                                    <h3><?= $attendance ?></h3>
                                                    <small>Present</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card text-center p-3">
                                                    <h3><?= $certificates ?></h3>
                                                    <small>Certificates</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <?php if($event['event_poster']): ?>
                                        <div class="event-detail-card">
                                            <h5>Event Poster</h5>
                                            <img src="<?= $event['event_poster'] ?>" alt="Event Poster" class="img-fluid">
                                            <div class="text-center mt-2">
                                                <a href="<?= $event['event_poster'] ?>" download class="download-btn">
                                                    <i class="fas fa-download"></i> Download Poster
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if($event['event_schedule']): ?>
                                        <div class="event-detail-card">
                                            <h5>Event Schedule</h5>
                                            <div class="text-center">
                                                <a href="<?= $event['event_schedule'] ?>" target="_blank" class="download-btn">
                                                    <i class="fas fa-file-pdf"></i> View Schedule
                                                </a>
                                                <a href="<?= $event['event_schedule'] ?>" download class="download-btn mt-2">
                                                    <i class="fas fa-download"></i> Download Schedule
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="event-detail-card">
                                        <h5>Quick Actions</h5>
                                        <div class="d-grid gap-2">
                                            <a href="index.php?page=attendance&event_id=<?= $event_id ?>" class="btn btn-primary">
                                                <i class="fas fa-check-circle"></i> Mark Attendance
                                            </a>
                                            <a href="index.php?page=bulk_certificates&event_id=<?= $event_id ?>" class="btn btn-warning">
                                                <i class="fas fa-layer-group"></i> Generate Certificates
                                            </a>
                                            <a href="index.php?export_report=1&type=event&id=<?= $event_id ?>" class="btn btn-success">
                                                <i class="fas fa-file-excel"></i> Download Report
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                @media print {
                    .navbar, .footer, .back-button-container, .btn, .download-btn, .d-grid {
                        display: none !important;
                    }
                    .card {
                        box-shadow: none !important;
                        border: 1px solid #ddd !important;
                    }
                    body {
                        background: white !important;
                    }
                }
            </style>
        <?php 
            }
        endif; 
        ?>

        <!-- 📊 Attendance Management with Search -->
        <?php if($page == 'attendance' && isset($_GET['event_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): 
            $event_id = intval($_GET['event_id']);
            $event = $conn->query("SELECT * FROM events WHERE id = $event_id")->fetch_assoc();
            
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $registered_students = $conn->query("SELECT s.*, a.status as attendance_status, r.certificate_issued, r.id as registration_id
                                                FROM students s 
                                                JOIN registrations r ON s.id = r.student_id 
                                                LEFT JOIN attendance a ON s.id = a.student_id AND a.event_id = $event_id AND a.marked_date = CURDATE()
                                                WHERE r.event_id = $event_id AND s.status = 'active'
                                                " . ($search ? "AND (s.name LIKE '%$search%' OR s.reg_no LIKE '%$search%' OR s.department LIKE '%$search%')" : "") . "
                                                ORDER BY s.reg_no");
        ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="page" value="attendance">
                    <input type="hidden" name="event_id" value="<?= $event_id ?>">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Search students by name, reg no, department..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn ms-2">Search</button>
                </form>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-clipboard-check"></i> Mark Attendance - <?= $event['title'] ?></span>
                            <div>
                                <button class="btn btn-light" data-bs-toggle="collapse" data-bs-target="#excelUpload">
                                    <i class="fas fa-file-excel"></i> Upload Excel
                                </button>
                                <a href="index.php?view_attendance_format=1" class="btn btn-info">
                                    <i class="fas fa-download"></i> Template
                                </a>
                                <a href="index.php?export_report=1&type=event&id=<?= $event_id ?>" class="btn btn-success">
                                    <i class="fas fa-download"></i> Download Report
                                </a>
                                <a href="index.php?bulk_certificates&event_id=<?= $event_id ?>" class="btn btn-warning">
                                    <i class="fas fa-layer-group"></i> Bulk Certificates
                                </a>
                            </div>
                        </div>
                        
                        <div class="collapse p-4" id="excelUpload">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5>📤 Upload Excel/CSV File</h5>
                                    <p class="text-muted">Format: Register No, Name, Department, Class, Year, Email, Status (present/absent/late)</p>
                                    <p class="text-warning"><i class="fas fa-exclamation-triangle"></i> Note: Absent students will NOT receive certificates</p>
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="event_id" value="<?= $event_id ?>">
                                        <div class="input-group">
                                            <input type="file" name="attendance_excel" class="form-control" accept=".csv,.xlsx" required>
                                            <button type="submit" class="btn btn-primary">Upload & Mark</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="event_id" value="<?= $event_id ?>">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Students marked as ABSENT will not receive certificates
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover datatable">
                                        <thead>
                                            <tr>
                                                <th>Register No</th>
                                                <th>Name</th>
                                                <th>Department</th>
                                                <th>Class</th>
                                                <th>Year</th>
                                                <th>Current Status</th>
                                                <th>Mark Attendance</th>
                                                <th>Certificate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($student = $registered_students->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= $student['reg_no'] ?></td>
                                                    <td><?= $student['name'] ?></td>
                                                    <td><?= $student['department'] ?></td>
                                                    <td><?= $student['class_level'] ?></td>
                                                    <td><?= $student['year_of_study'] ?></td>
                                                    <td>
                                                        <?php 
                                                        $status_class = '';
                                                        $status_icon = '';
                                                        switch($student['attendance_status']) {
                                                            case 'present':
                                                                $status_class = 'attendance-present';
                                                                $status_icon = '✅';
                                                                break;
                                                            case 'absent':
                                                                $status_class = 'attendance-absent';
                                                                $status_icon = '❌';
                                                                break;
                                                            case 'late':
                                                                $status_class = 'attendance-late';
                                                                $status_icon = '⏰';
                                                                break;
                                                            default:
                                                                $status_class = 'text-muted';
                                                                $status_icon = '⏳';
                                                        }
                                                        ?>
                                                        <span class="<?= $status_class ?>">
                                                            <?= $status_icon ?> <?= ucfirst($student['attendance_status'] ?? 'Not Marked') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <select name="attendance[<?= $student['id'] ?>]" class="form-control form-control-sm" style="width: 150px;">
                                                            <option value="present" <?= $student['attendance_status'] == 'present' ? 'selected' : '' ?>>✅ Present</option>
                                                            <option value="absent" <?= $student['attendance_status'] == 'absent' ? 'selected' : '' ?>>❌ Absent (No Certificate)</option>
                                                            <option value="late" <?= $student['attendance_status'] == 'late' ? 'selected' : '' ?>>⏰ Late</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <?php if($student['certificate_issued']): ?>
                                                            <span class="badge bg-success">Issued</span>
                                                        <?php elseif($student['attendance_status'] == 'absent'): ?>
                                                            <span class="badge bg-danger">No Certificate</span>
                                                        <?php else: ?>
                                                            <a href="index.php?generate_certificate=<?= $student['registration_id'] ?>" class="btn btn-sm btn-warning">
                                                                Generate
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" name="mark_attendance" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Save Attendance
                                    </button>
                                    <a href="index.php?page=events" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-arrow-left"></i> Back to Events
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 🎫 Bulk Certificates Generation Page with Search -->
        <?php if($page == 'bulk_certificates' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): 
            
            // If no event selected, show event selection page
            if(!isset($_GET['event_id'])): 
                $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
                $events = $conn->query("SELECT id, title, event_date FROM events 
                                        WHERE 1=1 " . ($search ? "AND (title LIKE '%$search%' OR event_date LIKE '%$search%')" : "") . " 
                                        ORDER BY event_date DESC");
            ?>
                <div class="back-button-container">
                    <a href="javascript:history.back()" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <!-- Search Box -->
                <div class="search-box">
                    <form method="GET" class="d-flex">
                        <input type="hidden" name="page" value="bulk_certificates">
                        <input type="text" name="search" class="form-control" placeholder="🔍 Search events..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn ms-2">Search</button>
                    </form>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-layer-group"></i> Bulk Certificate Generation - Select Event
                            </div>
                            <div class="card-body">
                                <?php if($events->num_rows > 0): ?>
                                    <div class="row">
                                        <?php while($ev = $events->fetch_assoc()): ?>
                                            <div class="col-md-4 mb-4">
                                                <div class="card h-100 hover-scale">
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?= $ev['title'] ?></h5>
                                                        <p class="card-text">
                                                            <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($ev['event_date'])) ?>
                                                        </p>
                                                        <a href="index.php?page=bulk_certificates&event_id=<?= $ev['id'] ?>" class="btn btn-primary w-100">
                                                            <i class="fas fa-certificate"></i> Generate Certificates
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                        <h4>No Events Found</h4>
                                        <p class="text-muted">Create an event first to generate certificates!</p>
                                        <a href="index.php?page=events" class="btn btn-primary">Create Event</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: 
                $event_id = intval($_GET['event_id']);
                $event = $conn->query("SELECT * FROM events WHERE id = $event_id")->fetch_assoc();
                
                if(!$event) {
                    echo '<div class="alert alert-danger">Event not found!</div>';
                } else {
                    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
                    $students = $conn->query("SELECT s.*, r.id as registration_id, r.attendance_status, 
                                             r.certificate_issued, r.certificate_no
                                      FROM students s
                                      JOIN registrations r ON s.id = r.student_id
                                      WHERE r.event_id = $event_id AND s.status = 'active'
                                      " . ($search ? "AND (s.name LIKE '%$search%' OR s.reg_no LIKE '%$search%' OR s.department LIKE '%$search%')" : "") . "
                                      ORDER BY s.name");
            ?>
                <div class="back-button-container">
                    <a href="javascript:history.back()" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <!-- Search Box -->
                <div class="search-box">
                    <form method="GET" class="d-flex">
                        <input type="hidden" name="page" value="bulk_certificates">
                        <input type="hidden" name="event_id" value="<?= $event_id ?>">
                        <input type="text" name="search" class="form-control" placeholder="🔍 Search students..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn ms-2">Search</button>
                    </form>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-layer-group"></i> Bulk Certificate Generation - <?= $event['title'] ?></span>
                                <div>
                                    <a href="index.php?bulk_download_certificates=1&event_id=<?= $event_id ?>" class="btn btn-success me-2">
                                        <i class="fas fa-download"></i> Download All as ZIP
                                    </a>
                                    <a href="index.php?page=bulk_certificates" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to Events
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong> Only students with "Present" or "Late" attendance status will receive certificates. Absent students are automatically skipped.
                                </div>
                                
                                <form method="POST" id="bulkCertificateForm">
                                    <input type="hidden" name="event_id" value="<?= $event_id ?>">
                                    
                                    <div class="select-all-container">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                                    <label class="form-check-label fw-bold" for="selectAll">
                                                        Select All Eligible Students (Present/Late)
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 text-end">
                                                <button type="submit" name="generate_multiple_certificates" class="btn btn-primary" onclick="return confirm('Generate certificates for selected students?')">
                                                    <i class="fas fa-certificate"></i> Generate Selected Certificates
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover datatable">
                                            <thead>
                                                <tr>
                                                    <th width="50">Select</th>
                                                    <th>Register No</th>
                                                    <th>Name</th>
                                                    <th>Department</th>
                                                    <th>Class</th>
                                                    <th>Year</th>
                                                    <th>Attendance Status</th>
                                                    <th>Certificate Status</th>
                                                    <th>Certificate No</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if($students && $students->num_rows > 0): ?>
                                                    <?php while($student = $students->fetch_assoc()): 
                                                        $eligible = ($student['attendance_status'] == 'present' || $student['attendance_status'] == 'late');
                                                    ?>
                                                        <tr class="<?= !$eligible ? 'table-danger' : '' ?>">
                                                            <td>
                                                                <input type="checkbox" name="selected_students[]" value="<?= $student['registration_id'] ?>" class="student-checkbox" 
                                                                       <?= $student['certificate_issued'] ? 'disabled' : '' ?>
                                                                       <?= !$eligible ? 'disabled' : '' ?>>
                                                            </td>
                                                            <td><?= $student['reg_no'] ?></td>
                                                            <td><?= $student['name'] ?></td>
                                                            <td><?= $student['department'] ?></td>
                                                            <td><?= $student['class_level'] ?></td>
                                                            <td><?= $student['year_of_study'] ?></td>
                                                            <td>
                                                                <?php 
                                                                switch($student['attendance_status']) {
                                                                    case 'present':
                                                                        echo '<span class="attendance-present">✅ Present</span>';
                                                                        break;
                                                                    case 'late':
                                                                        echo '<span class="attendance-late">⏰ Late</span>';
                                                                        break;
                                                                    case 'absent':
                                                                        echo '<span class="attendance-absent">❌ Absent</span>';
                                                                        break;
                                                                    default:
                                                                        echo '<span class="text-muted">⏳ Not Marked</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php if($student['certificate_issued']): ?>
                                                                    <span class="badge bg-success">Issued</span>
                                                                <?php elseif(!$eligible): ?>
                                                                    <span class="badge bg-danger">Not Eligible</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning">Not Issued</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?= $student['certificate_no'] ?? '—' ?>
                                                                <?php if($student['certificate_issued']): ?>
                                                                    <a href="index.php?download_certificate=<?= $student['registration_id'] ?>" class="btn btn-sm btn-link">
                                                                        <i class="fas fa-download"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if(!$student['certificate_issued'] && $eligible): ?>
                                                                    <a href="index.php?generate_certificate=<?= $student['registration_id'] ?>" class="btn btn-sm btn-success">
                                                                        <i class="fas fa-certificate"></i> Generate
                                                                    </a>
                                                                <?php endif; ?>
                                                                <?php if($student['certificate_issued']): ?>
                                                                    <button onclick="window.open('index.php?download_certificate=<?= $student['registration_id'] ?>&print=1', '_blank')" class="btn btn-sm btn-info">
                                                                        <i class="fas fa-print"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="10" class="text-center py-5">
                                                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                                            <h4>No Students Registered</h4>
                                                            <p class="text-muted">No students have registered for this event yet!</p>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <?php if($students && $students->num_rows > 0): ?>
                                        <div class="mt-3 text-center">
                                            <button type="submit" name="generate_multiple_certificates" class="btn btn-primary btn-lg">
                                                <i class="fas fa-certificate"></i> Generate Certificates for Selected Students
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <script>
                    document.getElementById('selectAll').addEventListener('change', function() {
                        var checkboxes = document.querySelectorAll('.student-checkbox:not(:disabled)');
                        for (var checkbox of checkboxes) {
                            checkbox.checked = this.checked;
                        }
                    });
                </script>
            <?php } endif; ?>
        <?php endif; ?>

        <!-- 🎫 Certificates Management with Search -->
        <?php if($page == 'certificates' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): 
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $certificates = $conn->query("SELECT c.*, e.title as event_title, s.name as student_name, s.reg_no,
                                         a.full_name as issued_by_name
                                         FROM certificates c
                                         JOIN events e ON c.event_id = e.id
                                         JOIN students s ON c.student_id = s.id
                                         LEFT JOIN admin a ON c.issued_by = a.id
                                         WHERE 1=1 " . 
                                         ($search ? "AND (c.certificate_no LIKE '%$search%' OR s.name LIKE '%$search%' OR s.reg_no LIKE '%$search%' OR e.title LIKE '%$search%')" : "") . "
                                         ORDER BY c.issue_date DESC LIMIT 50");
        ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="page" value="certificates">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Search by certificate no, student name, reg no, event..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn ms-2">Search</button>
                </form>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-certificate"></i> Certificate Management</span>
                            <div>
                                <select class="form-select form-select-sm" style="width: 200px; display: inline-block;" onchange="location = this.value;">
                                    <option value="">Select Event to Bulk Generate</option>
                                    <?php 
                                    $events_list = $conn->query("SELECT id, title FROM events WHERE event_date <= CURDATE() ORDER BY event_date DESC");
                                    while($ev = $events_list->fetch_assoc()) {
                                        echo "<option value='index.php?page=bulk_certificates&event_id={$ev['id']}'>📅 {$ev['title']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>Certificate No</th>
                                            <th>Student</th>
                                            <th>Event</th>
                                            <th>Issue Date</th>
                                            <th>Issued By</th>
                                            <th>Verification</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($certificates && $certificates->num_rows > 0): ?>
                                            <?php while($cert = $certificates->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong><?= $cert['certificate_no'] ?></strong></td>
                                                    <td>
                                                        <?= $cert['student_name'] ?><br>
                                                        <small><?= $cert['reg_no'] ?></small>
                                                    </td>
                                                    <td><?= $cert['event_title'] ?></td>
                                                    <td><?= date('d M Y', strtotime($cert['issue_date'])) ?></td>
                                                    <td><?= $cert['issued_by_name'] ?? 'System' ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?= substr($cert['verification_code'], 0, 10) ?>...</span>
                                                    </td>
                                                    <td>
                                                        <a href="index.php?download_certificate=<?= $cert['id'] ?>" class="btn btn-sm btn-success">
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                        <button onclick="window.open('index.php?download_certificate=<?= $cert['id'] ?>&print=1', '_blank')" class="btn btn-sm btn-info">
                                                            <i class="fas fa-print"></i> Print
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <i class="fas fa-certificate fa-4x text-muted mb-3"></i>
                                                    <h4>No Certificates Found</h4>
                                                    <p class="text-muted">Generate certificates for events to see them here!</p>
                                                    <a href="index.php?page=bulk_certificates" class="btn btn-primary">Generate Certificates</a>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 📈 Reports Section with Search -->
        <?php if($page == 'reports' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): 
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $event_reports = $conn->query("SELECT e.id, e.title, e.event_date, e.max_participants,
                                          COUNT(DISTINCT r.id) as total_registrations,
                                          COUNT(DISTINCT a.id) as attendance_count,
                                          COUNT(DISTINCT c.id) as certificate_count
                                          FROM events e
                                          LEFT JOIN registrations r ON e.id = r.event_id AND r.status = 'approved'
                                          LEFT JOIN attendance a ON e.id = a.event_id
                                          LEFT JOIN certificates c ON e.id = c.event_id
                                          WHERE 1=1 " . 
                                          ($search ? "AND (e.title LIKE '%$search%' OR e.event_date LIKE '%$search%' OR e.venue LIKE '%$search%')" : "") . "
                                          GROUP BY e.id
                                          ORDER BY e.event_date DESC");
        ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="page" value="reports">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Search events..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn ms-2">Search</button>
                </form>
            </div>

            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-chart-bar"></i> Event Reports</span>
                            <a href="index.php?export_report=1&type=all" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Export All
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>Date</th>
                                            <th>Capacity</th>
                                            <th>Registrations</th>
                                            <th>Attendance</th>
                                            <th>Certificates</th>
                                            <th>Participation %</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($event_reports && $event_reports->num_rows > 0): ?>
                                            <?php while($report = $event_reports->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong><?= $report['title'] ?></strong></td>
                                                    <td><?= date('d M Y', strtotime($report['event_date'])) ?></td>
                                                    <td><?= $report['max_participants'] ?></td>
                                                    <td><?= $report['total_registrations'] ?></td>
                                                    <td><?= $report['attendance_count'] ?></td>
                                                    <td><?= $report['certificate_count'] ?></td>
                                                    <td>
                                                        <?php 
                                                            $percentage = $report['total_registrations'] > 0 
                                                                ? round(($report['attendance_count'] / $report['total_registrations']) * 100, 2) 
                                                                : 0;
                                                        ?>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar" style="width: <?= $percentage ?>%">
                                                                <?= $percentage ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <a href="index.php?export_report=1&type=event&id=<?= $report['id'] ?>" class="btn btn-sm btn-success">
                                                            <i class="fas fa-file-excel"></i>
                                                        </a>
                                                        <a href="index.php?page=event_details&id=<?= $report['id'] ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                                                    <h4>No Reports Available</h4>
                                                    <p class="text-muted">Create events and track participation to see reports!</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-trophy"></i> Top Participants
                        </div>
                        <div class="card-body">
                            <?php 
                            $top_students = $conn->query("SELECT s.name, s.reg_no, s.department, s.class_level, s.year_of_study,
                                                         COUNT(DISTINCT r.event_id) as events_count,
                                                         COUNT(DISTINCT a.id) as attendance_count,
                                                         COUNT(DISTINCT c.id) as certificate_count
                                                         FROM students s
                                                         LEFT JOIN registrations r ON s.id = r.student_id
                                                         LEFT JOIN attendance a ON s.id = a.student_id
                                                         LEFT JOIN certificates c ON s.id = c.student_id
                                                         WHERE s.status = 'active'
                                                         GROUP BY s.id
                                                         ORDER BY events_count DESC
                                                         LIMIT 10");
                            ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Register No</th>
                                            <th>Name</th>
                                            <th>Department</th>
                                            <th>Class</th>
                                            <th>Year</th>
                                            <th>Events</th>
                                            <th>Attendance</th>
                                            <th>Certificates</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if($top_students && $top_students->num_rows > 0) {
                                            $rank = 1;
                                            while($student = $top_students->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td>
                                                    <?php if($rank == 1): ?>
                                                        🥇 Gold
                                                    <?php elseif($rank == 2): ?>
                                                        🥈 Silver
                                                    <?php elseif($rank == 3): ?>
                                                        🥉 Bronze
                                                    <?php else: ?>
                                                        #<?= $rank ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $student['reg_no'] ?></td>
                                                <td><?= $student['name'] ?></td>
                                                <td><?= $student['department'] ?></td>
                                                <td><?= $student['class_level'] ?></td>
                                                <td><?= $student['year_of_study'] ?></td>
                                                <td><span class="event-badge"><?= $student['events_count'] ?></span></td>
                                                <td><span class="badge bg-info"><?= $student['attendance_count'] ?></span></td>
                                                <td><span class="badge bg-warning"><?= $student['certificate_count'] ?></span></td>
                                            </tr>
                                        <?php 
                                            $rank++;
                                            endwhile; 
                                        } else {
                                            echo '<tr><td colspan="9" class="text-center py-3">No participant data available</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-pie"></i> Department Statistics
                        </div>
                        <div class="card-body">
                            <?php 
                            $dept_stats = $conn->query("SELECT department, class_level, 
                                                       COUNT(*) as student_count,
                                                       SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count
                                                       FROM students 
                                                       GROUP BY department, class_level
                                                       ORDER BY department, class_level");
                            ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Department</th>
                                            <th>Class</th>
                                            <th>Total Students</th>
                                            <th>Active</th>
                                            <th>Pending</th>
                                            <th>Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($dept_stats && $dept_stats->num_rows > 0): ?>
                                            <?php while($dept = $dept_stats->fetch_assoc()): 
                                                $pending = $dept['student_count'] - $dept['active_count'];
                                                $rate = $dept['student_count'] > 0 ? round(($dept['active_count'] / $dept['student_count']) * 100) : 0;
                                            ?>
                                                <tr>
                                                    <td><?= $dept['department'] ?></td>
                                                    <td><?= $dept['class_level'] ?></td>
                                                    <td><?= $dept['student_count'] ?></td>
                                                    <td><?= $dept['active_count'] ?></td>
                                                    <td><?= $pending ?></td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar" style="width: <?= $rate ?>%">
                                                                <?= $rate ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-3">No department statistics available</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 📝 Available Events for Students with Search -->
        <?php if($page == 'available_events' && isset($_SESSION['role']) && $_SESSION['role'] == 'student'): 
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $available = $conn->query("SELECT e.*, 
                                       (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) as registered,
                                       (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND student_id = {$_SESSION['user_id']}) as is_registered
                                       FROM events e 
                                       WHERE e.event_date >= CURDATE() 
                                       " . ($search ? "AND (e.title LIKE '%$search%' OR e.description LIKE '%$search%' OR e.venue LIKE '%$search%')" : "") . "
                                       ORDER BY e.event_date ASC");
        ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="page" value="available_events">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Search events..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn ms-2">Search</button>
                </form>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-calendar-alt"></i> Available Events
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php if($available->num_rows > 0): ?>
                                    <?php while($event = $available->fetch_assoc()): ?>
                                        <div class="col-md-4 mb-4">
                                            <div class="card h-100 hover-scale">
                                                <?php if($event['event_poster']): ?>
                                                    <img src="<?= $event['event_poster'] ?>" class="card-img-top" alt="Event Poster" style="height: 200px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div class="card-body">
                                                    <h5 class="card-title"><?= $event['title'] ?></h5>
                                                    <p class="card-text"><?= substr($event['description'], 0, 100) ?>...</p>
                                                    <div class="mb-2">
                                                        <small class="d-block"><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($event['event_date'])) ?></small>
                                                        <small class="d-block"><i class="fas fa-map-marker-alt"></i> <?= $event['venue'] ?></small>
                                                        <small class="d-block"><i class="fas fa-users"></i> <?= $event['registered'] ?>/<?= $event['max_participants'] ?> Registered</small>
                                                    </div>
                                                    <?php if($event['event_poster'] || $event['event_schedule']): ?>
                                                        <div class="mb-2">
                                                            <?php if($event['event_poster']): ?>
                                                                <a href="<?= $event['event_poster'] ?>" target="_blank" class="btn btn-sm btn-info">
                                                                    <i class="fas fa-image"></i> Poster
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if($event['event_schedule']): ?>
                                                                <a href="<?= $event['event_schedule'] ?>" target="_blank" class="btn btn-sm btn-info">
                                                                    <i class="fas fa-file-pdf"></i> Schedule
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if($event['is_registered'] > 0): ?>
                                                        <button class="btn btn-success w-100" disabled>✅ Already Registered</button>
                                                    <?php elseif($event['registered'] >= $event['max_participants']): ?>
                                                        <button class="btn btn-secondary w-100" disabled>❌ Full</button>
                                                    <?php else: ?>
                                                        <a href="index.php?register_event=<?= $event['id'] ?>" class="btn btn-primary w-100">📝 Register Now</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="col-12 text-center py-5">
                                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                        <h4>No Events Available</h4>
                                        <p class="text-muted">Check back later for new events!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 📋 My Registrations & Certificates with Search -->
        <?php if($page == 'my_registrations' && isset($_SESSION['role']) && $_SESSION['role'] == 'student'): 
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $my_events = $conn->query("SELECT e.*, r.registration_date, r.attendance_status, 
                                      r.certificate_issued, r.certificate_no
                                      FROM events e 
                                      JOIN registrations r ON e.id = r.event_id 
                                      WHERE r.student_id = {$_SESSION['user_id']} 
                                      " . ($search ? "AND (e.title LIKE '%$search%' OR e.description LIKE '%$search%' OR e.venue LIKE '%$search%')" : "") . "
                                      ORDER BY e.event_date DESC");
        ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="page" value="my_registrations">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Search your events..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn ms-2">Search</button>
                </form>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-clipboard-list"></i> My Registered Events
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>Event Name</th>
                                            <th>Date</th>
                                            <th>Venue</th>
                                            <th>Attendance</th>
                                            <th>Certificate</th>
                                            <th>Status</th>
                                            <th>Materials</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($my_events->num_rows > 0): ?>
                                            <?php while($event = $my_events->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong><?= $event['title'] ?></strong></td>
                                                    <td><?= date('d M Y', strtotime($event['event_date'])) ?></td>
                                                    <td><?= $event['venue'] ?></td>
                                                    <td>
                                                        <?php 
                                                        $att_class = '';
                                                        switch($event['attendance_status']) {
                                                            case 'present':
                                                                $att_class = 'attendance-present';
                                                                echo '<span class="' . $att_class . '">✅ Present</span>';
                                                                break;
                                                            case 'absent':
                                                                $att_class = 'attendance-absent';
                                                                echo '<span class="' . $att_class . '">❌ Absent</span>';
                                                                break;
                                                            case 'late':
                                                                $att_class = 'attendance-late';
                                                                echo '<span class="' . $att_class . '">⏰ Late</span>';
                                                                break;
                                                            default:
                                                                echo '<span class="text-muted">⏳ Not Marked</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if($event['certificate_issued']): ?>
                                                            <?php 
                                                            $cert = $conn->query("SELECT id FROM certificates 
                                                                                WHERE event_id = {$event['id']} 
                                                                                AND student_id = {$_SESSION['user_id']}")->fetch_assoc();
                                                            ?>
                                                            <a href="index.php?download_certificate=<?= $cert['id'] ?>" class="btn btn-sm btn-success">
                                                                <i class="fas fa-download"></i> Download
                                                            </a>
                                                        <?php elseif($event['attendance_status'] == 'absent'): ?>
                                                            <span class="badge bg-danger">No Certificate</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Not Issued</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($event['event_date'] > date('Y-m-d')): ?>
                                                            <span class="badge bg-success">Upcoming</span>
                                                        <?php elseif($event['event_date'] == date('Y-m-d')): ?>
                                                            <span class="badge bg-warning">Today</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Completed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($event['event_poster']): ?>
                                                            <a href="<?= $event['event_poster'] ?>" target="_blank" class="btn btn-sm btn-info">
                                                                <i class="fas fa-image"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if($event['event_schedule']): ?>
                                                            <a href="<?= $event['event_schedule'] ?>" target="_blank" class="btn btn-sm btn-info">
                                                                <i class="fas fa-file-pdf"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                                    <p>You haven't registered for any events yet!</p>
                                                    <a href="index.php?page=available_events" class="btn btn-primary">Browse Events</a>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 🎫 My Certificates with Search and Print -->
        <?php if($page == 'my_certificates' && isset($_SESSION['role']) && $_SESSION['role'] == 'student'): 
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $my_certs = $conn->query("SELECT c.*, e.title as event_title, e.event_date, e.event_poster
                                     FROM certificates c
                                     JOIN events e ON c.event_id = e.id
                                     WHERE c.student_id = {$_SESSION['user_id']}
                                     " . ($search ? "AND (c.certificate_no LIKE '%$search%' OR e.title LIKE '%$search%')" : "") . "
                                     ORDER BY c.issue_date DESC");
        ?>
            <div class="back-button-container">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="page" value="my_certificates">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Search certificates..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn ms-2">Search</button>
                </form>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-certificate"></i> My Certificates
                        </div>
                        <div class="card-body">
                            <?php if($my_certs->num_rows > 0): ?>
                                <div class="row">
                                    <?php while($cert = $my_certs->fetch_assoc()): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card certificate-preview">
                                                <?php if($cert['event_poster']): ?>
                                                    <img src="<?= $cert['event_poster'] ?>" class="card-img-top" alt="Event Poster" style="height: 150px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div class="card-body">
                                                    <h4 class="gradient-text">Certificate of Participation</h4>
                                                    <h5><?= $cert['event_title'] ?></h5>
                                                    <p>Certificate No: <?= $cert['certificate_no'] ?></p>
                                                    <p>Issued on: <?= date('d F Y', strtotime($cert['issue_date'])) ?></p>
                                                    <div class="d-flex gap-2">
                                                        <a href="index.php?download_certificate=<?= $cert['id'] ?>" class="btn btn-success">
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                        <button onclick="window.open('index.php?download_certificate=<?= $cert['id'] ?>&print=1', '_blank')" class="btn btn-info">
                                                            <i class="fas fa-print"></i> Print
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-certificate fa-4x text-muted mb-3"></i>
                                    <h4>No Certificates Yet</h4>
                                    <p class="text-muted">Participate in events to earn certificates!</p>
                                    <a href="index.php?page=available_events" class="btn btn-primary">Browse Events</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 📌 Footer -->
    <div class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>🎓 EventMaster</h5>
                    <p class="text-muted">Complete College Event Management Solution</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-muted">Home</a></li>
                        <li><a href="index.php?page=login" class="text-muted">Login</a></li>
                        <li><a href="index.php?page=register" class="text-muted">Student Register</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <p class="text-muted">
                        <i class="fas fa-envelope"></i> support@eventmaster.edu<br>
                        <i class="fas fa-phone"></i> +91 9876543210
                    </p>
                </div>
            </div>
            <hr>
            <p class="mb-0">🎓 College Event Management System | Developed by Thiru Project | © 2024</p>
            <p class="text-muted mt-2">
                <i class="fas fa-database"></i> MySQL | 
                <i class="fas fa-code"></i> PHP | 
                <i class="fas fa-paint-brush"></i> Bootstrap | 
                <i class="fas fa-certificate"></i> Certificate Ready
            </p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.datatable').DataTable({
                pageLength: 10,
                responsive: true,
                language: {
                    search: "🔍 Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "⏮️",
                        last: "⏭️",
                        next: "➡️",
                        previous: "⬅️"
                    }
                }
            });
        });
        
        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Confirmation for delete actions
        function confirmDelete(message) {
            return confirm(message || 'Are you sure?');
        }
        
        // Live search for tables
        function liveSearch(input, tableId) {
            var filter = input.value.toUpperCase();
            var table = document.getElementById(tableId);
            var tr = table.getElementsByTagName("tr");
            
            for (var i = 0; i < tr.length; i++) {
                var td = tr[i].getElementsByTagName("td")[1];
                if (td) {
                    var txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
        
        // Print certificate
        function printCertificate(certId) {
            var printWindow = window.open('index.php?download_certificate=' + certId + '&print=1', '_blank');
            printWindow.print();
        }
    </script>
</body>
</html> 