CREATE DATABASE IF NOT EXISTS portale_parrucchieri CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portale_parrucchieri;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL UNIQUE,
    description TEXT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    price DECIMAL(8,2) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    service_id INT UNSIGNED NOT NULL,
    guest_name VARCHAR(120) NOT NULL,
    guest_email VARCHAR(190) NOT NULL,
    guest_phone VARCHAR(40) NULL,
    appointment_at DATETIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    admin_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_appointments_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    INDEX idx_appointments_date (appointment_at),
    INDEX idx_appointments_status (status)
) ENGINE=InnoDB;

INSERT INTO users (name, email, phone, password_hash, role)
VALUES ('Admin Salone', 'admin@example.com', NULL, '$2y$12$D0BUZ9hMUGC/Gy7GYPFMyeGwjniaJ.7oQk.C7XZam0CGhsioHbaHK', 'admin')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO services (name, description, duration_minutes, price, is_active) VALUES
('Taglio uomo', 'Consulenza stile, taglio forbice/macchinetta e finishing.', 35, 24.00, 1),
('Barba ritual', 'Panno caldo, sagomatura barba e trattamento lenitivo.', 25, 18.00, 1),
('Combo taglio + barba', 'Esperienza completa con styling finale e prodotti premium.', 60, 38.00, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);
