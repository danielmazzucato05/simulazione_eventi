-- Drop existing tables and types to start fresh
DROP TABLE IF EXISTS iscrizioni CASCADE;
DROP TABLE IF EXISTS eventi CASCADE;
DROP TABLE IF EXISTS utenti CASCADE;
DROP TYPE IF EXISTS user_role CASCADE;

-- Create ENUM for user roles
CREATE TYPE user_role AS ENUM ('Dipendente', 'Organizzatore');

-- Table: Utente
CREATE TABLE utenti (
    utente_id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    ruolo user_role NOT NULL DEFAULT 'Dipendente',
    auth_token UUID DEFAULT NULL, -- Simplified token for stateless auth
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- Table: Evento
CREATE TABLE eventi (
    evento_id SERIAL PRIMARY KEY,
    titolo VARCHAR(200) NOT NULL,
    data TIMESTAMP WITH TIME ZONE NOT NULL,
    descrizione TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- Table: Iscrizione
CREATE TABLE iscrizioni (
    iscrizione_id SERIAL PRIMARY KEY,
    utente_id INT NOT NULL REFERENCES utenti(utente_id) ON DELETE CASCADE,
    evento_id INT NOT NULL REFERENCES eventi(evento_id) ON DELETE CASCADE,
    checkin_effettuato BOOLEAN NOT NULL DEFAULT FALSE,
    ora_checkin TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    -- Prevent duplicate registrations for the same user and event
    CONSTRAINT unique_user_event UNIQUE(utente_id, evento_id)
);

-- Note: In Supabase, you can run this script in the SQL Editor.

-- ==========================================
-- INSERIMENTO DATI DI TEST (DUMMY DATA)
-- Password per tutti gli utenti: password 
-- ==========================================

-- 1. Inserimento Utenti (10 record: 8 dipendenti, 2 organizzatori)
INSERT INTO utenti (nome, cognome, email, password_hash, ruolo) VALUES 
('Mario', 'Rossi', 'mario.rossi@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dipendente'),
('Laura', 'Bianchi', 'laura.bianchi@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dipendente'),
('Admin', 'Sistema', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Organizzatore'),
('Giulia', 'Verdi', 'giulia.verdi@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dipendente'),
('Luca', 'Neri', 'luca.neri@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dipendente'),
('Elena', 'Gialli', 'elena.gialli@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dipendente'),
('Marco', 'Marini', 'marco.marini@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dipendente'),
('Sara', 'Fabbri', 'sara.fabbri@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dipendente'),
('Alessandro', 'Galli', 'alessandro.galli@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dipendente'),
('Org', 'Esperto', 'org.esperto@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Organizzatore');

-- 2. Inserimento Eventi (10 record: 4 passati, 6 futuri)
INSERT INTO eventi (titolo, data, descrizione) VALUES
('Corso Sicurezza sul Lavoro', CURRENT_TIMESTAMP - INTERVAL '30 days', 'Corso obbligatorio sulla sicurezza. (Passato)'),
('Workshop React', CURRENT_TIMESTAMP - INTERVAL '20 days', 'Approfondimento su React. (Passato)'),
('Agile & Scrum', CURRENT_TIMESTAMP - INTERVAL '10 days', 'Introduzione ad Agile. (Passato)'),
('Comunicazione Efficace', CURRENT_TIMESTAMP - INTERVAL '5 days', 'Migliorare le soft skills. (Passato)'),
('Corso Primo Soccorso', CURRENT_TIMESTAMP + INTERVAL '5 days', 'Certificazione di primo soccorso.'),
('Design Thinking', CURRENT_TIMESTAMP + INTERVAL '10 days', 'Sessione su design thinking.'),
('Advanced SQL', CURRENT_TIMESTAMP + INTERVAL '15 days', 'Ottimizzazione e query complesse in SQL.'),
('Team Building', CURRENT_TIMESTAMP + INTERVAL '20 days', 'Giornata aziendale di team building.'),
('AWS Cloud Basics', CURRENT_TIMESTAMP + INTERVAL '30 days', 'Introduzione al cloud AWS.'),
('Leadership & Management', CURRENT_TIMESTAMP + INTERVAL '40 days', 'Corso per futuri manager.');

-- 3. Inserimento Iscrizioni (15 record incrociati)
INSERT INTO iscrizioni (utente_id, evento_id, checkin_effettuato, ora_checkin) VALUES
(1, 1, TRUE, CURRENT_TIMESTAMP - INTERVAL '30 days'),
(2, 1, TRUE, CURRENT_TIMESTAMP - INTERVAL '30 days'),
(4, 1, TRUE, CURRENT_TIMESTAMP - INTERVAL '30 days'),
(5, 2, TRUE, CURRENT_TIMESTAMP - INTERVAL '20 days'),
(6, 2, TRUE, CURRENT_TIMESTAMP - INTERVAL '20 days'),
(7, 2, FALSE, NULL),
(1, 3, FALSE, NULL),
(8, 3, TRUE, CURRENT_TIMESTAMP - INTERVAL '10 days'),
(9, 3, TRUE, CURRENT_TIMESTAMP - INTERVAL '10 days'),
(1, 6, FALSE, NULL),
(2, 6, FALSE, NULL),
(8, 6, FALSE, NULL),
(5, 7, FALSE, NULL),
(6, 7, FALSE, NULL),
(4, 8, FALSE, NULL);
