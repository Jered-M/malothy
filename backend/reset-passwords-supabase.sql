INSERT INTO users (name, email, password, role, status)
VALUES
    ('Administrateur', 'admin@maloty.com', '$2y$12$5RRkMsNu7e1Sx8fxFUM/i.5tPT6mv2D97UWiCpEXlJB5xRqYYy7j.', 'admin', 'actif'),
    ('Trésorier', 'treasure@maloty.com', '$2y$12$X94B/Wo.RvINSjw3oT7kT.myY5N4ld44pjHqVhp4BJVgQJEY6efxm', 'trésorier', 'actif'),
    ('Secrétaire', 'secretary@maloty.com', '$2y$12$o7V7U/0x7vk7jlQCm8lZeuZY/dJlyp7d2j.EOnbZAHU4GWLT7ihn6', 'secrétaire', 'actif')
ON CONFLICT (email) DO UPDATE
SET
    name = EXCLUDED.name,
    password = EXCLUDED.password,
    role = EXCLUDED.role,
    status = EXCLUDED.status;
