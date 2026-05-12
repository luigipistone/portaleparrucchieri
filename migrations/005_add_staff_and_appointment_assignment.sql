USE portale_parrucchieri;

CREATE TABLE IF NOT EXISTS staff_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    role_title VARCHAR(120) NOT NULL DEFAULT 'Barber',
    bio TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO staff_members (name, role_title, bio, is_active) VALUES
('Marco Rossi', 'Senior barber', 'Specialista taglio uomo, sfumature e consulenza stile.', 1),
('Luca Bianchi', 'Barber & beard specialist', 'Cura barba, rituali rasatura e trattamenti premium.', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

ALTER TABLE appointments
    ADD COLUMN staff_id INT UNSIGNED NULL AFTER service_id,
    ADD CONSTRAINT fk_appointments_staff FOREIGN KEY (staff_id) REFERENCES staff_members(id) ON DELETE SET NULL,
    ADD INDEX idx_appointments_staff_date (staff_id, appointment_at);
