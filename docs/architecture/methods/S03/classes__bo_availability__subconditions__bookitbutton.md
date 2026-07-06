# bookitbutton — Methoden-Doku
**Datei:** `classes/bo_availability/subconditions/bookitbutton.php` · **LOC:** 205 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`bookitbutton` ist die hartkodierte Basis-/Schluss-Condition der Subbooking-Kette (implementiert `bo_subcondition`). Sie gibt absichtlich immer `false` zurueck, weil sie als letzte Pruefung in der Conditions-Kette steht und so den „Book it"-Button erzeugt — das „Blockieren" bedeutet hier nicht Sperre, sondern Buchbarkeit. Kein DB-Zustand; id hartkodiert (`MOD_BOOKING_BO_COND_BOOKITBUTTON`). Kollaborateure: `booking_option_settings`, Sprachstring `booknow`, das `mod_booking/bookit_button`-Template. Anders als die anderen Subconditions arbeitet `render_button` auf `area => 'subbooking'` mit `itemid => $subbookingid`.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert die hartkodierte Condition-id. **Seiteneffekte:** keine. **Rueckgabe:** `MOD_BOOKING_BO_COND_BOOKITBUTTON`. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Markiert die Condition als nicht JSON-konfigurierbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Markiert die Condition als nicht im Options-mform sichtbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, $subbookingid, $userid, $not = false): bool` — public
- **Zweck:** Gibt immer `false` zurueck, damit der Bookit-Button stets als letzte Kettenglied-Aktion erscheint. **Seiteneffekte:** keine. **Rueckgabe:** `false` (konstant). **Bewertung:** B — beachte: `$not` wird hier nicht ausgewertet; eine Invertierung wuerde ignoriert. Fuer den festen Schluss-Charakter dieser Condition bewusst, aber abweichend von den Geschwister-Conditions.

### `public function get_description(booking_option_settings $settings, $subbookingid, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert das 4-Tupel fuer den Bookit-Flow. **Seiteneffekte:** ruft `is_available()` und `get_description_string()`. **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_BOOK, MOD_BOOKING_BO_BUTTON_MYBUTTON]`. **Bewertung:** B — toter Zwischenwert `$description = ''` (sofort ueberschrieben); im Gegensatz zu den anderen Conditions PREPAGE_BOOK/MYBUTTON, was den Buchungs-Prepage triggert.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid, $subbookingid)` — public
- **Zweck:** No-op — keine Form-Elemente. **Seiteneffekte:** keine. **Rueckgabe:** void. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $subbookingid, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Baut die Renderdaten fuer den sekundaeren Bookit-Button (`btn btn-secondary`, role button) im Subbooking-Bereich. **Seiteneffekte:** liest global `$USER`; ruft `get_description_string()`. **Rueckgabe:** `['mod_booking/bookit_button', $data]` mit `itemid => $subbookingid`, `area => 'subbooking'`. **Bewertung:** C — wie bei den Geschwister-Conditions ist der `$USER`-Fallback toter Code (Default `int $userid = 0`, nie `=== null`); ein Aufruf ohne userid liefert `userid => 0`.

### `public function get_description_string()` — public
- **Zweck:** Liefert konstant den Sprachstring `booknow` (Blockieren = buchbar, keine Verfuegbarkeits-Differenzierung). **Seiteneffekte:** `get_string('booknow', 'mod_booking')`. **Rueckgabe:** string. **Bewertung:** A.

### Triviale Properties
`public $id` (Z.51).

## Bewertungs-Resümee
Bewusst minimalistische Schluss-Condition der Kette: immer `false`, fester `booknow`-Button. Schwaechen rein kosmetisch — toter `$description`-Vorbeleger und der unerreichbare `$USER`-Fallback in `render_button`; zudem ignoriert `is_available` das `$not`-Flag. Keine funktionalen Risiken. Klassen-Score **B / P3**.
