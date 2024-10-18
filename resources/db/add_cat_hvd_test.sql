-- Entfernen von Einträgen mit fkey_custom_category_id = 20 (empty category)
DELETE FROM mb_metadata_custom_category
WHERE fkey_custom_category_id = 20;

-- Einfügen für fkey_custom_category_id = 25 (Geospatial)
INSERT INTO mb_metadata_custom_category (fkey_metadata_id, fkey_custom_category_id)
SELECT fkey_metadata_id, 25
FROM md_termsofuse 
WHERE fkey_termsofuse_id IN (
    SELECT termsofuse_id 
    FROM termsofuse 
    WHERE isopen = 1
)
AND fkey_metadata_id IN (
    SELECT fkey_metadata_id 
    FROM mb_metadata_inspire_category 
    WHERE fkey_inspire_category_id IN (3, 4, 5, 6, 15)
)
AND NOT EXISTS (
    SELECT 1 
    FROM mb_metadata_custom_category 
    WHERE fkey_metadata_id = md_termsofuse.fkey_metadata_id 
    AND fkey_custom_category_id = 25
);

-- Einfügen für fkey_custom_category_id = 26 (Mobility)
INSERT INTO mb_metadata_custom_category (fkey_metadata_id, fkey_custom_category_id)
SELECT fkey_metadata_id, 26
FROM md_termsofuse 
WHERE fkey_termsofuse_id IN (
    SELECT termsofuse_id 
    FROM termsofuse 
    WHERE isopen = 1
)
AND fkey_metadata_id IN (
    SELECT fkey_metadata_id 
    FROM mb_metadata_inspire_category 
    WHERE fkey_inspire_category_id IN (7)
)
AND NOT EXISTS (
    SELECT 1 
    FROM mb_metadata_custom_category 
    WHERE fkey_metadata_id = md_termsofuse.fkey_metadata_id 
    AND fkey_custom_category_id = 26
);

-- Einfügen für fkey_custom_category_id = 23 (Earth observation and environment)
INSERT INTO mb_metadata_custom_category (fkey_metadata_id, fkey_custom_category_id)
SELECT fkey_metadata_id, 23
FROM md_termsofuse 
WHERE fkey_termsofuse_id IN (
    SELECT termsofuse_id 
    FROM termsofuse 
    WHERE isopen = 1
)
AND fkey_metadata_id IN (
    SELECT fkey_metadata_id 
    FROM mb_metadata_inspire_category 
    WHERE fkey_inspire_category_id IN (8, 9, 10, 11, 12, 13, 16, 17, 20, 21, 24, 25, 28, 29, 30, 31, 32, 33, 34)
)
AND NOT EXISTS (
    SELECT 1 
    FROM mb_metadata_custom_category 
    WHERE fkey_metadata_id = md_termsofuse.fkey_metadata_id 
    AND fkey_custom_category_id = 23
);
