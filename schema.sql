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
