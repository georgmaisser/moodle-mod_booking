# confirmbookit — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/confirmbookit.php` · **LOC:** 287 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`confirmbookit` ist eine hardcodierte Aktions-`bo_condition` (id `MOD_BOOKING_BO_COND_CONFIRMBOOKIT` = -80) und liefert den Bestaetigungsschritt („Sind Sie sicher?") fuer die einfache Bookit-Buchung. Sie ist nicht JSON-konfigurierbar und nicht im mform sichtbar. Kern ist ein Cache-basierter Anti-Doppelklick-/Confirm-Mechanismus: per-User-Cache `confirmbooking` haelt Zeitstempel der zuletzt angeforderten Bestaetigung; innerhalb von `MOD_BOOKING_TIME_TO_CONFIRM` Sekunden blockiert die Condition, danach ist sie wieder available. Persistenz: MUC-Cache `mod_booking/confirmbooking` (per-User-Key). Kollaborateure: `cache`, `bo_info::render_button`, geschwister-Klasse `bookitbutton` (Override-Condition-ids), `booking_option_settings`.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert die hardcodierte Condition-id. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Markiert die Condition als nicht JSON-konfigurierbar. **Rueckgabe:** immer false. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Sichtbarkeit im Options-mform. **Rueckgabe:** immer false. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Anzeigename (`bocondconfirmbookit`). **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Ueberspringbarkeit. **Rueckgabe:** immer false. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Prueft via per-User-Cache, ob die Bestaetigung gerade „frisch" gesetzt wurde (dann blocken) oder die Confirm-Frist abgelaufen ist (dann available). **Seiteneffekte:** `cache::make('mod_booking','confirmbooking')`; bei Cache-Miss `$cache->set($userid, [])`; respektiert `$not`-Inversion. **Rueckgabe:** bool. **Bewertung:** B — `global $DB;` wird deklariert, aber nie genutzt (toter Import). Der `$cachekey` (`{userid}_{settings->id}_bookit`) wird gebildet, aber `$cachedata` enthaelt die per-User-Map; Logik ist nachvollziehbar, aber die explizite `$cache->set($userid, [])`-Vorbefuellung ist bei `$cachedata === false` redundant zur folgenden Available-Verzweigung.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionales SQL. **Rueckgabe:** Leer-Tupel. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block vor der Buchung. **Rueckgabe:** immer true. **Bewertung:** A — als Aktions-Condition soll der Bestaetigungsschritt die Buchung hart abfangen, bis bestaetigt.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Verfuegbarkeit + Beschreibung + Prepage/Button-Typ. **Seiteneffekte:** ruft `is_available()` und `get_description_string()`. **Rueckgabe:** `[$isavailable, 'Are you sure: book', MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_MYBUTTON]`. **Bewertung:** B — uebergibt `$userid = null` an `is_available()`, dessen Signatur jedoch `int $userid` (nicht-nullable) erwartet; bei direktem Aufruf mit null TypeError. Praktisch wird `get_description` mit konkretem userid aufgerufen, daher latent.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Form-Elemente. **Seiteneffekte:** keine (No-op). **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Zusatz-Prepage. **Rueckgabe:** leeres Array (keine eigene Seite). **Bewertung:** A.

### `public function render_button(...): array` — public
- **Zweck:** Rendert den „Are you sure: book"-Button (btn-warning) via `bo_info::render_button`; bei `multiplebookings` haengt es `overrideids` (json-codierte Book-Intent-Override-Condition-ids aus `bookitbutton`) an die Button-Daten. **Seiteneffekte:** `$USER`-Fallback; `bo_info::render_button(...)`; ggf. `bookitbutton::get_book_intent_override_condition_ids()`. **Rueckgabe:** `[$template, $data]`. **Bewertung:** B — `if ($userid === null)` ist toter Code, da `$userid` als `int = 0` typisiert ist und nie null sein kann (der `$USER`-Fallback greift folglich nie bei tatsaechlich fehlendem userid 0).

### `public function get_description_string()` — public
- **Zweck:** Lokalisierter Bestaetigungstext (`areyousure:book`). **Seiteneffekte:** `get_string`. **Rueckgabe:** string. **Bewertung:** A.

### Triviale Properties
`$id` (Condition-id) und `$overwrittenbybillboard` (false).

## Bewertungs-Resümee
Funktionierende Confirm-/Anti-Doppelklick-Condition mit per-User-Cache. Schwaechen sind durchweg P3: ungenutztes `global $DB`, der nutzlose `$userid === null`-Check bei int-Typ, und der null-Default-Durchstich von `get_description` an die nicht-nullable `is_available`-Signatur. Kein Datenverlust-/Sicherheitsrisiko. Klassen-Score **B / P3**.
