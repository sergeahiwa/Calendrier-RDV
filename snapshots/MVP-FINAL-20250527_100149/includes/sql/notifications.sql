-- Création de la table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    rdv_id INT,
    prestataire_id INT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_lue DATETIME NULL,
    lue BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (admin_id) REFERENCES admins(id),
    FOREIGN KEY (rdv_id) REFERENCES reservations(id),
    FOREIGN KEY (prestataire_id) REFERENCES prestataires(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Création des index
CREATE INDEX idx_admin_id ON notifications(admin_id);
CREATE INDEX idx_date_creation ON notifications(date_creation);
CREATE INDEX idx_lue ON notifications(lue);
