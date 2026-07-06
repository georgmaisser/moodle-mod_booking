# subbookings — Methoden-Doku
**Datei:** `classes/subbookings.php` · **LOC:** 98 · **Subsystem:** S08 (Subbookings) · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S08_*.md)

## Klassenueberblick
`subbookings` ist eine schlanke (Alt-/Teil-)Klasse rund um Zusatzbuchungs-Antworten (Tabelle `booking_subbooking_answers`). Sie sollte laut Konstruktor-Kommentar Subbooking-Daten cachen, tut dies aber unvollstaendig; die einzige funktionale Methode schreibt eine Subbooking-Antwort direkt in die DB. Die meiste Subbooking-Logik liegt anderswo (`subbookings\subbookings_info`, `sb_types/`). Kollaborateure: `$DB`, `$USER`, Cache `mod_booking/subbookings`. Persistenz (schreibend): `booking_subbooking_answers`. Importiert mehrere Klassen (`entitiesrelation_handler`, `booking_handler`, `moodle_url`, `context_*`), die im sichtbaren Code ungenutzt sind (Dead-Imports).

## Methoden

### `public function __construct(int $optionid)` — public
- **Zweck:** Soll den Subbooking-Cache fuer eine Option laden. **Seiteneffekte:** `cache::make('mod_booking','subbookings')->get($optionid)`. **Bewertung:** D — **toter Konstruktor:** ermittelt `$savecache` (true/false), nutzt das Ergebnis aber nie und befuellt weder Properties noch Cache (`subbookings.php:50-56`). Das Property `$id` bleibt immer `null`. Effektiv ein No-op mit verschwendetem Cache-Read.

### `public function user_submit_response(int $userid, int $sboid, string $json = '', int $timestart = 0, int $timeend = 0, bool $addedtocart = false)` — public
- **Zweck:** Schreibt eine Subbooking-Antwort fuer einen User. **Seiteneffekte:** `$DB->insert_record('booking_subbooking_answers', ...)` mit Status BOOKED (bei `addedtocart`) bzw. RESERVED. **Bewertung:** C — **`$json`-Parameter wird ignoriert:** das Record-Feld `json` wird hart auf `''` gesetzt statt den uebergebenen `$json` zu verwenden (`subbookings.php:88`) — wahrscheinlicher Bug (Subbooking-Detaildaten gehen verloren). Reines Insert ohne Duplikatpruefung/Upsert.

### Triviale Properties
`id` (public, wird nie gesetzt — bleibt null).

## Bewertungs-Resümee
Wirkt wie ein unfertiges/teilweise verwaistes Fragment: toter Konstruktor (Cache-Read ohne Wirkung, `$id` nie gesetzt), `user_submit_response` ignoriert den `$json`-Parameter (Datenverlust-Risiko), mehrere Dead-Imports. Funktional riskant trotz geringer Groesse. Klassen-Score **C / P1**; der ignorierte `$json` und der No-op-Konstruktor sind REFACTORING_BACKLOG-Kandidaten.
