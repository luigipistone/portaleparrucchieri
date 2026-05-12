USE portale_parrucchieri;

ALTER TABLE appointments
    ADD COLUMN booking_token VARCHAR(64) NULL AFTER admin_notes,
    ADD UNIQUE INDEX idx_appointments_booking_token (booking_token);
