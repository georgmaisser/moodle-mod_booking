# confirmbookwithsubscription — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/confirmbookwithsubscription.php` · **LOC:** 284 · **Subsystem:** S03 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`confirmbookwithsubscription` ist eine hardcodierte Aktions-`bo_condition` (id `MOD_BOOKING_BO_COND_CONFIRMBOOKWITHSUBSCRIPTION` = -20): der Bestaetigungsschritt fuer die Abo-/Subscription-Buchung. Die Klasse ist ein nahezu wortwoertlicher Klon von `confirmbookit`/`confirmbookwithcredits` mit demselben per-User-Cache-Confirm-Mechanismus (Cachekey-Suffix `_bookwithsubscription`) — allerdings ist dieser Mechanismus aktuell stillgelegt: `is_available()` enthaelt ein unbedingtes `return true;` vor dem gesamten Cache-Block. Persistenz (geplant): MUC-Cache `mod_booking/confirmbooking`. Kollaborateure: `cache`, `bo_info::render_button`, `booking_option_settings`.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert die hardcodierte Condition-id. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Nicht JSON-konfigurierbar. **Rueckgabe:** false. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im mform sichtbar. **Rueckgabe:** false. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Anzeigename (`bocondconfirmbookwithsubscription`). **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Ueberspringbarkeit. **Rueckgabe:** false. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Soll (analog zu den Schwester-Klassen) den Cache-basierten Confirm-Status pruefen. **Seiteneffekte:** keine wirksamen — die Methode beginnt mit `$isavailable = false;` gefolgt von einem unbedingten `return true;` (Z.115). **Rueckgabe:** immer true. **Bewertung:** D — der komplette Cache-/Confirm-Block (Z.117-146), inkl. `$not`-Inversion, ist nach dem `return true;` toter, unerreichbarer Code. Die Condition ist damit faktisch ein No-op: der Abo-Bestaetigungsschritt blockt nie. Falls Absicht (Feature noch nicht aktiv), gehoert der tote Block entfernt; falls nicht, ist der Confirm-Schutz fuer Abo-Buchungen wirkungslos.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionales SQL. **Rueckgabe:** Leer-Tupel. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block vor der Buchung. **Rueckgabe:** immer true. **Bewertung:** A.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Verfuegbarkeit + Beschreibung + Prepage/Button-Typ. **Seiteneffekte:** `is_available()` (liefert stets true), daher Beschreibung immer ''. **Rueckgabe:** `[true, '', MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_MYBUTTON]`. **Bewertung:** C — ruft `$this->get_description_string($isavailable, $full, $settings)` mit drei Argumenten auf, obwohl die Methode parameterlos deklariert ist (PHP ignoriert die Extra-Args still); der Zweig ist wegen des konstanten true ohnehin unerreichbar.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Form-Elemente. **Seiteneffekte:** keine (No-op). **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Zusatz-Prepage. **Rueckgabe:** leeres Array. **Bewertung:** A.

### `public function render_button(...): array` — public
- **Zweck:** Rendert den btn-warning-Button via `bo_info::render_button`. **Seiteneffekte:** `$USER`-Fallback; `bo_info::render_button(...)`. **Rueckgabe:** `[$template, $data]`. **Bewertung:** B — `if ($userid === null)` toter Code bei int-Parameter.

### `public function get_description_string()` — public
- **Zweck:** Bestaetigungstext (`areyousure:book`). **Seiteneffekte:** `get_string`. **Rueckgabe:** string. **Bewertung:** A — Signatur parameterlos, wird aber an einer Stelle mit 3 Args aufgerufen (siehe `get_description`).

### Triviale Properties
`$id` und `$overwrittenbybillboard` (false).

## Bewertungs-Resümee
Funktional die schwaechste der confirmbook*-Klassen: das vorgezogene `return true;` macht `is_available()` zum No-op und entwertet rund 30 Zeilen Cache-Logik als unerreichbaren Code. Dazu der Arg-Count-Mismatch im `get_description_string`-Aufruf und der uebliche tote null-Check. Kein akuter Datenverlust, aber der Abo-Confirm-Schutz greift nicht. Klassen-Score **C / P3**.
