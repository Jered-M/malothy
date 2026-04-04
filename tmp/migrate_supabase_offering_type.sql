-- Supabase / PostgreSQL
-- Ajouter le type "cotisation" aux offrandes
ALTER TYPE offering_type ADD VALUE IF NOT EXISTS 'cotisation';
