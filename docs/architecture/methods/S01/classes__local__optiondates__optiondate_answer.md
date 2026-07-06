# optiondate_answer — Methoden-Doku
**Datei:** `classes/local/optiondates/optiondate_answer.php` · **LOC:** 166 · **Subsystem:** S01 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S01_core_domain.md)

## Klassenueberblick
`optiondate_answer` ist ein schlankes DTO/Repository fuer Antworten pro Einzeltermin einer Buchungsoption (z.B. Praesenz-Status und Notizen pro Sitzung), gespeichert in `booking_optiondates_answers`. Die Identitaet eines Datensatzes ist das Tripel `(userid, optiondateid, optionid)`, das im Konstruktor fixiert wird; alle Methoden operieren CRUD-artig gegen `$DB` auf genau diesem Schluessel. Persistenz: Tabelle `booking_optiondates_answers`. Kollaborateure: `$DB`, `global $USER` (fuer `usermodified`). Erneut traegt der Klassen-Doc-Block faelschlich „cartstore"-Text.

## Methoden

### `public function __construct($userid, $optiondateid, $optionid)` — public
- **Zweck:** Fixiert das Identitaets-Tripel der Instanz. **Seiteneffekte:** keine. **Bewertung:** A — untypisierte Parameter (keine `int`-Hints), aber unkritisch.

### `public function save_record($status = null, $notes = null, $json = null)` — public
- **Zweck:** Upsert: aktualisiert den vorhandenen Datensatz (per Tripel-Lookup) oder legt einen neuen an; beim Update werden `notes`/`status` mit dem Bestand gemerged (`$notes ?? $existing->notes`) und `timecreated` bewahrt. **Seiteneffekte:** `global $DB, $USER`; `$DB->get_record(...)`, dann `update_record` oder `insert_record`; setzt `timemodified`/`timecreated`/`usermodified=$USER->id`. **Rueckgabe:** bool (update) bzw. neue id/bool (insert) — gemischter Rueckgabetyp. **Bewertung:** B — sinnvolles Merge-Verhalten (partielle Updates moeglich, da nur explizit uebergebene Felder ueberschreiben). Schwaeche: nicht atomar — der `get_record`+`insert/update`-Pfad hat ein Race-Window; bei zwei parallelen Submits derselben Identitaet koennen zwei Inserts entstehen, sofern kein DB-Unique-Index auf `(userid, optiondateid, optionid)` existiert (zu verifizieren). `$json` wird beim Update nicht gemerged (immer ueberschrieben, anders als notes/status).

### `public function get_record()` — public
- **Zweck:** Liest den Datensatz fuer das Tripel. **Seiteneffekte:** `$DB->get_record(...)`. **Rueckgabe:** stdClass oder false (Phpdoc sagt „null"). **Bewertung:** A.

### `public function delete_record()` — public
- **Zweck:** Loescht den Datensatz fuer das Tripel. **Seiteneffekte:** `$DB->delete_records(...)`. **Rueckgabe:** bool. **Bewertung:** A.

### `public function get_records_for_optiondate()` — public
- **Zweck:** Liefert ALLE Antworten zu `(optiondateid, optionid)` ueber alle User (z.B. fuer Praesenzliste). **Seiteneffekte:** `$DB->get_records(...)`. **Rueckgabe:** array von Records. **Bewertung:** B — ignoriert das `userid` der Instanz; korrekt fuer den Zweck, aber inkonsistent zum sonst tripel-basierten Verhalten der Klasse (potenzielle Verwechslungsgefahr beim Aufruf).

### `public function add_or_update_status($status)` — public
- **Zweck:** Komfort-Wrapper, setzt nur den Status via `save_record($status)`. **Seiteneffekte:** wie `save_record`. **Rueckgabe:** bool. **Bewertung:** A.

### `public function add_or_update_notes($note)` — public
- **Zweck:** Komfort-Wrapper, setzt nur die Notiz via `save_record(null, $note)`. **Seiteneffekte:** wie `save_record`. **Rueckgabe:** bool. **Bewertung:** A.

### Triviale Properties
Drei private Properties (`userid`, `optiondateid`, `optionid`, Z.40–53) als Identitaets-Halter.

## Bewertungs-Resümee
Sauberes, kompaktes CRUD-DTO mit durchdachtem partiellem Merge-Verhalten (notes/status werden bei Teilupdates bewahrt). Schwachpunkte: der nicht-atomare Upsert in `save_record` (Race-Window ohne garantierten Unique-Index), `$json` wird im Update-Pfad nicht gemerged, und `get_records_for_optiondate` durchbricht das sonstige Tripel-Schema. Funktional unkritisch im typischen Einzel-User-Submit. Klassen-Score **B / P3**.
