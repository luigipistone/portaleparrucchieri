USE portale_parrucchieri;

INSERT INTO users (name, email, phone, password_hash, role)
VALUES ('Admin Salone', 'admin@example.com', NULL, '$2y$12$D0BUZ9hMUGC/Gy7GYPFMyeGwjniaJ.7oQk.C7XZam0CGhsioHbaHK', 'admin')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO services (name, description, duration_minutes, price, is_active) VALUES
('Taglio uomo', 'Consulenza stile, taglio forbice/macchinetta e finishing.', 35, 24.00, 1),
('Barba ritual', 'Panno caldo, sagomatura barba e trattamento lenitivo.', 25, 18.00, 1),
('Combo taglio + barba', 'Esperienza completa con styling finale e prodotti premium.', 60, 38.00, 1)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    duration_minutes = VALUES(duration_minutes),
    price = VALUES(price),
    is_active = VALUES(is_active);
