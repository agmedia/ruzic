-- OPG Ruzic: uklanjanje triju zamijenjenih izvornika iz javne arhive.
-- U phpMyAdminu odabrati produkcijsku bazu opgruzic_2023.
-- Tocni parovi potvrdeni na javnom cjeniku 2. 10. 2026.: 1->4, 2->5, 3->6.
-- Ne brise datoteke ni revizijski trag i ne mijenja vremena objave.
-- Ponovno izvrsavanje nema dodatni ucinak. Status superseded je povratno uklanjanje.

UPDATE `oc_anchor_price_publication` AS o
JOIN `oc_anchor_price_publication` AS c
  ON c.corrects_publication_id = o.publication_id
 AND c.store_id = o.store_id
 AND c.location_code = o.location_code
SET o.status = 'superseded'
WHERE o.publication_id IN (1, 2, 3)
  AND c.publication_id IN (4, 5, 6)
  AND o.store_id = 0
  AND o.location_code = 'WEB'
  AND o.corrects_publication_id IS NULL
  AND o.status = 'published'
  AND c.status = 'published'
  AND c.source_published_at = o.published_at;

SELECT publication_id, corrects_publication_id, published_at, source_published_at, status
FROM `oc_anchor_price_publication`
WHERE publication_id IN (1, 2, 3, 4, 5, 6)
ORDER BY publication_id;
