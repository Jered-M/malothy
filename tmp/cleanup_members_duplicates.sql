-- Supabase / PostgreSQL
-- Nettoyer les doublons membres par email ou telephone (garder le plus petit id)

WITH ranked AS (
    SELECT
        id,
        email,
        phone,
        ROW_NUMBER() OVER (PARTITION BY LOWER(email) ORDER BY id) AS rn_email,
        ROW_NUMBER() OVER (PARTITION BY phone ORDER BY id) AS rn_phone
    FROM members
)
DELETE FROM members
WHERE id IN (
    SELECT id FROM ranked
    WHERE (email IS NOT NULL AND email <> '' AND rn_email > 1)
       OR (phone IS NOT NULL AND phone <> '' AND rn_phone > 1)
);

-- Ajouter des contraintes d'unicite (a executer apres nettoyage)
-- CREATE UNIQUE INDEX IF NOT EXISTS members_email_unique ON members (LOWER(email));
-- CREATE UNIQUE INDEX IF NOT EXISTS members_phone_unique ON members (phone);
