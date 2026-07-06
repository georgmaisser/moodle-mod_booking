# entity_mutation_service — Methoden-Doku
**Datei:** `classes/local/wizard/services/mutation/entity_mutation_service.php` · **LOC:** 97 · **Subsystem:** S15 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_ai.md)

## Klassenueberblick
`entity_mutation_service` ist ein Application-Service fuer das Anlegen von Entities (`local_wb_entity`) mit Dedup-Vorpruefung auf Name und Shortname. Der Schreibpfad selbst ist aktuell **nicht implementiert**: `create_entity()` fuehrt alle Validierungen durch, gibt dann aber bedingungslos einen Platzhalter-Fehler zurueck („Entity creation service not yet available"). Persistenz: liest gegen Tabelle `local_wb_entity` (mit Existenz-Guard via `get_manager()->table_exists`), schreibt (noch) nicht. `declare(strict_types=1)`. Kollaborateure: `create_entity_input_dto` (Eingabe), `mutation_result_dto` (Ergebnis, aus `bookingextension_agent`), `$DB`.

## Methoden

### `public function create_entity(create_entity_input_dto $dto, int $userid): mutation_result_dto` — public
- **Zweck:** Validiert eine Entity-Erstellung (nicht-leerer Name, Name-Dedup, optional Shortname-Dedup) und soll danach die Erstellung an das Entities-Plugin delegieren. **Seiteneffekte:** `entity_exists_by_name`/`entity_exists_by_shortname` (DB-Lesezugriffe); aktuell **kein** Write. **Rueckgabe:** stets `mutation_result_dto::error(...)` — entweder Validierungsfehler oder der unbedingte „not yet available"-Fehler (Z.67). **Bewertung:** B — Validierungs-/Dedup-Logik korrekt, aber der Schreibpfad ist ein bewusst markierter Stub: bei gueltiger, nicht-duplizierter Eingabe liefert die Methode trotzdem einen Fehler. `$userid` wird derzeit nicht verwendet. Funktional inaktiv (kein Datenrisiko), aber irrefuehrend, falls Aufrufer Erfolg erwarten.

### `public function entity_exists_by_name(string $name): bool` — public
- **Zweck:** Prueft, ob ein Entity-Record mit gegebenem `name` existiert. **Seiteneffekte:** `$DB->get_manager()->table_exists('local_wb_entity')` als Guard (gibt `false`, wenn Tabelle fehlt), sonst `$DB->record_exists`. **Rueckgabe:** bool. **Bewertung:** A — defensiver Tabellen-Guard fuer Installationen ohne Entities-Plugin.

### `public function entity_exists_by_shortname(string $shortname): bool` — public
- **Zweck:** Wie oben, fuer `shortname`. **Seiteneffekte:** identischer table_exists-Guard + `record_exists`. **Rueckgabe:** bool. **Bewertung:** B — funktional korrekt, dupliziert aber `entity_exists_by_name` bis auf das Feld (haetten ein gemeinsamer Helper sein koennen).

## Bewertungs-Resümee
Dedup-Vorpruefung ist sauber und defensiv (table_exists-Guard verhindert Fehler ohne Entities-Plugin), aber der eigentliche Schreibpfad ist ein noch nicht implementierter Stub: `create_entity` gibt selbst bei gueltiger Eingabe immer einen Fehler zurueck. Solange kein Aufrufer Erfolg erwartet, ungefaehrlich; sonst eine Falle. Leichte Duplikation der beiden Existenz-Checks. Klassen-Score **B / P3**.
