USE portale_parrucchieri;

ALTER TABLE appointments
    MODIFY status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending';
