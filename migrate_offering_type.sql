-- ============================================================================
-- Migration: Add 'nature' and 'espece' to offering_type ENUM
-- ============================================================================
-- This migration adds missing values to the PostgreSQL offering_type ENUM
-- to support the new secretary in-kind donation workflow.

-- Add 'nature' value if it doesn't exist
ALTER TYPE offering_type ADD VALUE IF NOT EXISTS 'nature';

-- Add 'espece' value if it doesn't exist  
ALTER TYPE offering_type ADD VALUE IF NOT EXISTS 'espece';

-- Verify the ENUM has been updated
-- SELECT e.enumlabel FROM pg_enum e
-- JOIN pg_type t ON e.enumtypid = t.oid
-- WHERE t.typname = 'offering_type'
-- ORDER BY e.enumsortorder;
