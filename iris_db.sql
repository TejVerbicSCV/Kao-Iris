-- Create database
CREATE DATABASE IF NOT EXISTS iris_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE iris_db;

-- Create roles table
CREATE TABLE IF NOT EXISTS vloge (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naziv VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO vloge (naziv) VALUES 
('admin'),
('zdravnik'),
('pacient');

-- Create users table
CREATE TABLE IF NOT EXISTS uporabniki (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ime VARCHAR(50) NOT NULL,
    priimek VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefon VARCHAR(20),
    naslov TEXT,
    geslo VARCHAR(255) NOT NULL,
    zdravnik_id INT,
    vloga_id INT NOT NULL,
    specializacija VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vloga_id) REFERENCES vloge(id),
    FOREIGN KEY (zdravnik_id) REFERENCES uporabniki(id)
);

-- Create prescriptions table
CREATE TABLE IF NOT EXISTS recepti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uporabnik_id INT NOT NULL,
    zdravnik_id INT NOT NULL,
    zdravilo VARCHAR(100) NOT NULL,
    doza VARCHAR(50) NOT NULL,
    navodila TEXT,
    datum_izdaje DATE NOT NULL,
    datum_poteka DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uporabnik_id) REFERENCES uporabniki(id),
    FOREIGN KEY (zdravnik_id) REFERENCES uporabniki(id)
);

-- Create referrals table
CREATE TABLE IF NOT EXISTS napotnice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uporabnik_id INT NOT NULL,
    zdravnik_id INT NOT NULL,
    specializacija VARCHAR(100) NOT NULL,
    ustanova VARCHAR(100) NOT NULL,
    zadeva VARCHAR(200) NOT NULL,
    razlog TEXT NOT NULL,
    nujnost ENUM('nujno', 'obstojno', 'planirano') NOT NULL,
    datum_izdaje DATE NOT NULL,
    datum_pregleda DATE NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    opombe TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uporabnik_id) REFERENCES uporabniki(id),
    FOREIGN KEY (zdravnik_id) REFERENCES uporabniki(id)
);

-- Create conversations table
CREATE TABLE IF NOT EXISTS pogovori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uporabnik_id INT NOT NULL,
    zdravnik_id INT NOT NULL,
    zadeva VARCHAR(200) NOT NULL,
    sporocilo TEXT NOT NULL,
    datum_poslano DATETIME NOT NULL,
    prebrano BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uporabnik_id) REFERENCES uporabniki(id),
    FOREIGN KEY (zdravnik_id) REFERENCES uporabniki(id)
);

-- Create sick leaves table
CREATE TABLE IF NOT EXISTS bolniske (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uporabnik_id INT NOT NULL,
    zdravnik_id INT NOT NULL,
    razlog TEXT NOT NULL,
    datum_zacetka DATE NOT NULL,
    datum_konca DATE NOT NULL,
    opombe TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uporabnik_id) REFERENCES uporabniki(id),
    FOREIGN KEY (zdravnik_id) REFERENCES uporabniki(id)
); 