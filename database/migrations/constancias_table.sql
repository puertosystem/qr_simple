CREATE TABLE IF NOT EXISTS constancias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    evento_id INT NOT NULL,
    codigo_verificacion VARCHAR(50) NOT NULL UNIQUE,
    qr_codigo VARCHAR(255) NOT NULL,
    fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_generacion VARCHAR(45),
    user_agent VARCHAR(255),
    FOREIGN KEY (lead_id) REFERENCES constancia_leads(id) ON DELETE CASCADE,
    FOREIGN KEY (evento_id) REFERENCES constancia_eventos(id) ON DELETE CASCADE
);
