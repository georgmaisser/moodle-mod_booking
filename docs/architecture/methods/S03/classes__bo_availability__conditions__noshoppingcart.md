# noshoppingcart — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/noshoppingcart.php` · **LOC:** 254 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`noshoppingcart` ist eine hartcodierte `bo_condition` (id `MOD_BOOKING_BO_COND_NOSHOPPINGCART`, numerisch -60). Sie modelliert den Fall „fuer die Option ist ein Preis gesetzt, aber `local_shopping_cart` ist nicht installiert" — dann ist die normale Buchung nicht moeglich (Buchung nur via Payment). Ist kein Preis gesetzt oder die Cart-Klasse vorhanden, ist die Bedingung erfuellt. Keine eigene Persistenz (`is_json_compatible()` = false, nicht im mform sichtbar); Zustand kommt ausschliesslich aus `booking_option_settings->jsonobject->useprice`. Kollaborateure: `price` (Button-Rendering), `singleton_service` (User-Lookup), `local_shopping_cart\shopping_cart` (nur via `class_exists`). Implementiert das vollstaendige `bo_condition`-Interface, das die meisten Methoden trivial bedient.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert die hartcodierte Condition-id. **Seiteneffekte:** keine. **Rueckgabe:** `$this->id`. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Markiert die Condition als nicht JSON-konfigurierbar (hardcoded). **Seiteneffekte:** keine. **Rueckgabe:** konstant `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Steuert, ob die Condition im Options-Formular erscheint. **Seiteneffekte:** keine. **Rueckgabe:** konstant `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Anzeigename (`bocondnoshoppingcart`). **Seiteneffekte:** keine. **Rueckgabe:** Sprachstring. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Ob die Condition im Buchungsprozess uebersprungen werden darf. **Seiteneffekte:** keine. **Rueckgabe:** konstant `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernlogik. Verfuegbar, wenn kein Preis gesetzt ist (`empty($settings->jsonobject->useprice)`) ODER die Shopping-Cart-Klasse existiert; sonst blockiert. **Seiteneffekte:** `class_exists('local_shopping_cart\shopping_cart')` (Autoload-Trigger). Beachtet `$not`-Inversion. **Rueckgabe:** bool. **Bewertung:** B — die OR-Logik ist korrekt fuer den Zweck (Preis ohne Cart -> Block), aber durch den negierten Klassennamen + doppelte Negation schwer lesbar; `$userid` wird nicht verwendet.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionaler SQL-Filter zum Ausblenden (statt nur Blocken). **Seiteneffekte:** keine. **Rueckgabe:** Leeres 5-Tupel `['', '', '', [], '']` (kein SQL-Beitrag). **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Sperre direkt vor der Buchung (nur geprueft, wenn `is_available` bereits false). **Seiteneffekte:** keine. **Rueckgabe:** konstant `true` (echte Sperre, kein Bypass moeglich). **Bewertung:** A.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Beschreibungs-Tupel fuer Anzeige/Buttonsteuerung. **Seiteneffekte:** ruft `is_available()`. **Rueckgabe:** `[$isavailable, 'noshoppingcart', MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_JUSTMYALERT]`. **Bewertung:** B — die Description ist hier ein roher Bezeichner `'noshoppingcart'` statt eines lokalisierten Strings; die zuvor gesetzte `$description = ''` ist toter Vorbelegungs-Code.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Formular-Hook. **Seiteneffekte:** keine (No-op, da nicht konfigurierbar). **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Liefert Daten fuer eine optionale Prepage. **Seiteneffekte:** keine. **Rueckgabe:** statisches Array mit leeren Daten und `buttontype => 0` (Continue aktiv). **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Preis-Block (`mod_booking/col_price`). **Seiteneffekte:** `singleton_service::get_instance_of_user($userid)`, `price::get_price('option', $settings->id, $user)`. **Rueckgabe:** `['mod_booking/col_price', $returnarray]` mit `priceitems` und optional `fullwidth`. **Bewertung:** B — `$returnarray` wird nicht initialisiert, bevor `$returnarray['priceitems']` gesetzt wird (in PHP zulaessig per Auto-Vivification, aber stilistisch unsauber); `$full`/`$not` ungenutzt.

## Bewertungs-Resümee
Schlanke, hartcodierte Condition; das meiste Interface ist trivial bedient. Funktional korrekt; Schwaechen sind kosmetisch: roher Description-Bezeichner statt Sprachstring, schwer lesbare doppelt-negierte Verfuegbarkeitslogik, nicht initialisiertes `$returnarray`. Keine Daten-/Sicherheitsrisiken. Klassen-Score **B / P3**.
