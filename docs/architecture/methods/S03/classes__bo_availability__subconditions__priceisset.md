# priceisset — Methoden-Doku
**Datei:** `classes/bo_availability/subconditions/priceisset.php` · **LOC:** 221 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`priceisset` ist eine hartkodierte Subbooking-Availability-Condition (implementiert `bo_subcondition`). Sie blockiert die normale Buchung, wenn ein Preis gesetzt ist — Buchung dann nur ueber den Warenkorb-/Payment-Flow. Verfuegbarkeit (true, also „normal buchbar ohne Preis") gilt nur, wenn die Option `useprice` nicht gesetzt hat UND das Subbooking keine Preis-Items hat. Kein DB-Zustand; id hartkodiert (`MOD_BOOKING_BO_COND_PRICEISSET`). Kollaborateure: `price::get_prices_from_cache_or_db`, `singleton_service` (booking_option_settings, user), `booking_option_settings::return_subbooking_option_information`, Sprachstrings, `mod_booking/bookit_price`-Template.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert die hartkodierte Condition-id. **Seiteneffekte:** keine. **Rueckgabe:** `MOD_BOOKING_BO_COND_PRICEISSET`. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Nicht JSON-konfigurierbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im Options-mform sichtbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $subbookingid, int $userid, $not = false): bool` — public
- **Zweck:** Liefert true (normal buchbar) nur, wenn `empty($settings->jsonobject->useprice)` UND das Subbooking keine Preis-Items hat. **Seiteneffekte:** `price::get_prices_from_cache_or_db('option', $settings->id, $userid)` — dessen Ergebnis wird jedoch sofort ueberschrieben und nie verwendet; dann (nur im `empty(useprice)`-Zweig) `price::get_prices_from_cache_or_db('subbooking', $subbookingid)`. **Rueckgabe:** bool, ggf. durch `$not` invertiert. **Bewertung:** C — die erste Preisabfrage auf `'option'` ist verschwendet: ihr Rueckgabewert wird unmittelbar in Z.99 ueberschrieben, ohne ausgewertet zu werden (toter Cache-/DB-Zugriff, vgl. Findings).

### `public function get_description(booking_option_settings $settings, $subbookingid, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert das 4-Tupel fuer die Anzeige. **Seiteneffekte:** ruft `is_available()` (inkl. Preisabfragen) und `get_description_string()`. **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_MYBUTTON]`. **Bewertung:** B — `$userid` Default `null` fliesst in `is_available(int $userid)`; ohne strict_types zu `0` gecastet. Toter `$description = ''`-Vorbeleger.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0, $subbookingid = 0)` — public
- **Zweck:** No-op — keine Form-Elemente. **Seiteneffekte:** keine. **Rueckgabe:** void. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $subbookingid, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Baut die Preis-/Warenkorb-Renderdaten fuer das Subbooking (deaktiviert das automatische Weiterleiten via `dataaction => 'noforward'`). **Seiteneffekte:** `$userid = !empty($userid) ? $userid : $USER->id` (korrekter Fallback); `singleton_service::get_instance_of_booking_option_settings($settings->id)`, `get_instance_of_user($userid)`; `settings->return_subbooking_option_information($subbookingid, $user)`. **Rueckgabe:** `['mod_booking/bookit_price', $data]`. **Bewertung:** B — anders als die Geschwister-Conditions korrekter `$USER`-Fallback (`!empty`) und eigenes `bookit_price`-Template; re-resolvt `$settings` per singleton_service (defensive Doppelauflösung).

### `public function get_description_string($isavailable, $full)` — public
- **Zweck:** Liefert lokalisierten String je nach Verfuegbarkeit/Voll-Sicht (`bocondpriceisset*`). **Seiteneffekte:** `get_string(...)`. **Rueckgabe:** string. **Bewertung:** A.

### Triviale Properties
`public $id` (Z.52).

## Bewertungs-Resümee
Funktional korrekte Preis-Gate-Subcondition mit sauberem `render_button` (korrekter `$USER`-Fallback, eigenes Warenkorb-Template). Hauptschwaeche: die ungenutzte erste `get_prices_from_cache_or_db('option', ...)`-Abfrage in `is_available` ist ein verschwendeter Cache-/DB-Zugriff pro Aufruf. Daneben kosmetisch: toter `$description`-Vorbeleger, `int`/`null`-Mismatch (ohne strict_types unkritisch). Klassen-Score **B / P3**.
