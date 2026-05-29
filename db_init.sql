CREATE DATABASE IF NOT EXISTS smart_procurement CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_procurement;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','supplier','superadmin') NOT NULL DEFAULT 'supplier',
    company_name VARCHAR(191) DEFAULT NULL,
    services_offered TEXT DEFAULT NULL,
    past_experience TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX(role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (name, email, password_hash, role, created_at)
SELECT 'Super Admin', 'superadmin@smartprocurement.local', '$2y$10$ql/S8.Sbyy7J0ijndbZJ2urZtdgRmKTqiOyzF7DWX482IoyvDIuLm', 'superadmin', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'superadmin@smartprocurement.local');

CREATE TABLE IF NOT EXISTS procurements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    budget VARCHAR(100) DEFAULT NULL,
    delivery_days INT NOT NULL,
    submission_deadline DATE NOT NULL,
    evaluation_criteria VARCHAR(255) DEFAULT NULL,
    support_document VARCHAR(255) DEFAULT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL,
    INDEX(status),
    INDEX(submission_deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    procurement_id INT NOT NULL,
    supplier_id INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    delivery_days INT NOT NULL,
    remarks TEXT DEFAULT NULL,
    proposal_document VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','awarded','rejected') NOT NULL DEFAULT 'pending',
    delivery_status VARCHAR(50) DEFAULT 'unknown',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (procurement_id) REFERENCES procurements(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(procurement_id),
    INDEX(supplier_id),
    INDEX(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(191) NOT NULL,
    details TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT DEFAULT 0,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id),
    INDEX(is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
