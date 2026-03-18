-- Create ENUM for user roles
CREATE TYPE user_role AS ENUM ('Dipendente', 'Organizzatore');

-- Table: Utente
CREATE TABLE utenti (
    utente_id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
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
    evento_id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    titolo VARCHAR(200) NOT NULL,
    data TIMESTAMP WITH TIME ZONE NOT NULL,
    descrizione TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- Table: Iscrizione
CREATE TABLE iscrizioni (
    iscrizione_id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    utente_id UUID NOT NULL REFERENCES utenti(utente_id) ON DELETE CASCADE,
    evento_id UUID NOT NULL REFERENCES eventi(evento_id) ON DELETE CASCADE,
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

-- 1. Inserimento Utenti
INSERT INTO utenti (utente_id, nome, cognome, email, password_hash, ruolo) VALUES 
('11111111-1111-1111-1111-111111111111', 'Mario', 'Rossi', 'mario.rossi@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dipendente'),
('22222222-2222-2222-2222-222222222222', 'Laura', 'Bianchi', 'laura.bianchi@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dipendente'),
('33333333-3333-3333-3333-333333333333', 'Admin', 'Sistema', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Organizzatore');

-- 2. Inserimento Eventi (1 passato, 2 futuri)
INSERT INTO eventi (evento_id, titolo, data, descrizione) VALUES
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'Corso Sicurezza sul Lavoro', CURRENT_TIMESTAMP - INTERVAL '10 days', 'Corso obbligatorio sulla sicurezza aggiornato al 2026. (Evento Passato per testare Statistiche e storico)'),
('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', 'Workshop React Advanced', CURRENT_TIMESTAMP + INTERVAL '15 days', 'Approfondimento su React, Hooks e Context API. Frontend CSR architecture.'),
('cccccccc-cccc-cccc-cccc-cccccccccccc', 'Comunicazione Efficace', CURRENT_TIMESTAMP + INTERVAL '30 days', 'Come migliorare la comunicazione nel team e le soft skills.');

-- 3. Inserimento Iscrizioni
-- Mario (111) ha partecipato all'evento passato (aaa) e ha fatto il check-in
-- Laura (222) si era iscritta all'evento passato (aaa) ma NON ha fatto il check-in
-- Mario (111) è iscritto all'evento futuro (bbb)
INSERT INTO iscrizioni (utente_id, evento_id, checkin_effettuato, ora_checkin) VALUES
('11111111-1111-1111-1111-111111111111', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', TRUE, CURRENT_TIMESTAMP - INTERVAL '10 days'),
('22222222-2222-2222-2222-222222222222', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', FALSE, NULL),
('11111111-1111-1111-1111-111111111111', 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', FALSE, NULL);
