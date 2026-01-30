-- ============================================================================
-- CRONJOB Konfiguration (PostgreSQL):
-- ============================================================================
-- Füge folgende Zeile in die crontab ein (z.B. täglich um 00:30 Uhr):
-- 
-- Mit statischem Logfile-Namen (überschreibt sich täglich):
-- 30 0 * * * su - username -c "psql -q -p portnumber -d databasename -f /path/to/mapbender/resources/db/rm_opendata.sql" >> /var/log/opendata_cleanup.log 2>&1
--
-- Mit Datum im Logfile-Namen (neue Datei pro Durchlauf):
-- 30 0 * * * su - username -c "psql -q -p portnumber -d databasename -f /path/to/mapbender/resources/db/rm_opendata.sql" >> /var/log/opendata_cleanup_$(date +\%Y\%m\%d).log 2>&1
--
-- Beispiel mit Zeitstempel (einzigartige Datei pro Durchlauf):
-- 30 0 * * * su - username -c "psql -q -p portnumber -d databasename -f /path/to/mapbender/resources/db/rm_opendata.sql" >> /var/log/opendata_cleanup_$(date +\%Y\%m\%d_\%H\%M\%S).log 2>&1
-- ============================================================================
-- Entfernt alle "open data" Varianten (außer Biotopentwicklung mit ID 5806)
-- (je nach instanz andere außnahmen notwendig)
-- aus den Lookup-Tabellen und der Keyword-Tabelle

\echo '-- Starte OpenData Keyword Cleanup'
\echo '-- Datum: '`date`
\echo '-- Diese können mit psql wieder eingespielt werden'
-- ===================================================================
-- 1. BACKUP: layer_keyword
-- ===================================================================
\echo ''
\echo ''
\echo '-- layer_keyword'
SELECT 'INSERT INTO layer_keyword (fkey_layer_id, fkey_keyword_id) VALUES (' || fkey_layer_id || ', ' || fkey_keyword_id || ');' 
FROM layer_keyword 
WHERE fkey_keyword_id IN (SELECT keyword_id FROM keyword WHERE keyword LIKE '%open%' AND keyword_id NOT IN (5806))
ORDER BY fkey_layer_id, fkey_keyword_id;

\echo '-- Gelöschte Einträge in layer_keyword:'
SELECT '-- ' || COUNT(*) || ' Einträge gelöscht' FROM layer_keyword 
WHERE fkey_keyword_id IN (SELECT keyword_id FROM keyword WHERE keyword LIKE '%open%' AND keyword_id NOT IN (5806));

-- ===================================================================
-- 2. BACKUP: mb_metadata_keyword
-- ===================================================================
\echo ''
\echo '-- mb_metadata_keyword'
SELECT 'INSERT INTO mb_metadata_keyword (fkey_metadata_id, fkey_keyword_id) VALUES (' || fkey_metadata_id || ', ' || fkey_keyword_id || ');'
FROM mb_metadata_keyword 
WHERE fkey_keyword_id IN (SELECT keyword_id FROM keyword WHERE keyword LIKE '%open%' AND keyword_id NOT IN (5806))
ORDER BY fkey_metadata_id, fkey_keyword_id;

\echo '-- Gelöschte Einträge in mb_metadata_keyword:'
SELECT '-- ' || COUNT(*) || ' Einträge gelöscht' FROM mb_metadata_keyword 
WHERE fkey_keyword_id IN (SELECT keyword_id FROM keyword WHERE keyword LIKE '%open%' AND keyword_id NOT IN (5806));

-- ===================================================================
-- 3. BACKUP: wfs_featuretype_keyword
-- ===================================================================
\echo ''
\echo '-- wfs_featuretype_keyword'
SELECT 'INSERT INTO wfs_featuretype_keyword (fkey_featuretype_id, fkey_keyword_id) VALUES (' || fkey_featuretype_id || ', ' || fkey_keyword_id || ');'
FROM wfs_featuretype_keyword 
WHERE fkey_keyword_id IN (SELECT keyword_id FROM keyword WHERE keyword LIKE '%open%' AND keyword_id NOT IN (5806))
ORDER BY fkey_featuretype_id, fkey_keyword_id;

\echo '-- Gelös\echo ''
\echo '-- wmc_keyword'
chte Einträge in wfs_featuretype_keyword:'
SELECT '-- ' || COUNT(*) || ' Einträge gelöscht' FROM wfs_featuretype_keyword 
WHERE fkey_keyword_id IN (SELECT keyword_id FROM keyword WHERE keyword LIKE '%open%' AND keyword_id NOT IN (5806));

-- ===================================================================
-- 4. BACKUP: wmc_keyword
-- ===================================================================
\echo ''
\echo '-- wmc_keyword'
SELECT 'INSERT INTO wmc_keyword (fkey_keyword_id, fkey_wmc_serial_id) VALUES (' || fkey_keyword_id || ', ' || fkey_wmc_serial_id || ');'
FROM wmc_keyword 
WHERE fkey_keyword_id IN (SELECT keyword_id FROM keyword WHERE keyword LIKE '%open%' AND keyword_id NOT IN (5806))
ORDER BY fkey_wmc_serial_id, fkey_keyword_id;

\echo '-- Gelöschte Einträge in wmc_keyword:'
SELECT '-- ' || COUNT(*) || ' Einträge gelöscht' FROM wmc_keyword 
WHERE fkey_keyword_id IN (SELECT keyword_id FROM keyword WHERE keyword LIKE '%open%' AND keyword_id NOT IN (5806));

-- ===================================================================
-- 5. DELETE: Jetzt löschen
-- ===================================================================
\echo ''
\echo '-- Starte Löschvorgänge'

DELETE FROM layer_keyword 
WHERE fkey_keyword_id IN (
  SELECT keyword_id FROM keyword 
  WHERE keyword LIKE '%open%' 
  AND keyword_id NOT IN (5806)
);

DELETE FROM mb_metadata_keyword 
WHERE fkey_keyword_id IN (
  SELECT keyword_id FROM keyword 
  WHERE keyword LIKE '%open%' 
  AND keyword_id NOT IN (5806)
);

DELETE FROM wfs_featuretype_keyword 
WHERE fkey_keyword_id IN (
  SELECT keyword_id FROM keyword 
  WHERE keyword LIKE '%open%' 
  AND keyword_id NOT IN (5806)
);

DELETE FROM wmc_keyword 
WHERE fkey_keyword_id IN (
  SELECT keyword_id FROM keyword 
  WHERE keyword LIKE '%open%' 
  AND keyword_id NOT IN (5806)
);

\echo ''
\echo '-- Cleanup abgeschlossen'
\echo '-- Hinweis: Keywords selbst wurden NICHT gelöscht'
