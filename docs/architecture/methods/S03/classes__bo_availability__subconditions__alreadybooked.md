# alreadybooked — Methoden-Doku
**Datei:** `classes/bo_availability/subconditions/alreadybooked.php` · **LOC:** 233 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`alreadybooked` ist eine hartkodierte Subbooking-Availability-Condition (implementiert `bo_subcondition`), die prueft, ob der User ein gegebenes Subbooking bereits gebucht hat. Trotz `implements bo_subcondition` lautet der Klassen-Doc-Kommentar irrefuehrend „extends this class" (Copy-Paste aus der Basisklassen-Vorlage). Kein eigener DB-Zustand; die id ist hartkodiert (`MOD_BOOKING_BO_COND_ALREADYBOOKED`). Kollaborateure: `singleton_service::get_instance_of_booking_answers`, `booking_answers` (`return_all_booking_information`, `subbooking_user_status`), `booking_option_settings`, Sprachstrings, das `mod_booking/bookit_button`-Template.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert die hartkodierte Condition-id. **Seiteneffekte:** keine. **Rueckgabe:** `$this->id` (`MOD_BOOKING_BO_COND_ALREADYBOOKED`). **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Signalisiert, dass diese hartkodierte Condition keine JSON-Konfiguration aufnimmt. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Signalisiert, dass die Condition nicht im Options-mform erscheint. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, $subbookingid, $userid, $not = false): bool` — public
- **Zweck:** Verfuegbar (true), solange der User das Subbooking noch nicht gebucht hat; nicht verfuegbar, sobald er es gebucht hat. Logik: ist der User ueberhaupt nicht in der Option gebucht (`iambooked` nicht gesetzt), gilt true; ist er gebucht, entscheidet `subbooking_user_status` (BOOKED -> false, sonst true). **Seiteneffekte:** `singleton_service::get_instance_of_booking_answers($settings)`; ruft `return_all_booking_information($userid)` und `subbooking_user_status($subbookingid, $userid)`. **Rueckgabe:** bool, ggf. durch `$not` invertiert. **Bewertung:** B — `$userid` wird ungeprueft (kein global $USER-Fallback) an die Lookups durchgereicht; korrekt nur, wenn der Aufrufer immer eine echte id liefert.

### `public function get_description(booking_option_settings $settings, $subbookingid, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert das 4-Tupel `[verfuegbar, beschreibung, prepage, button]` fuer die Anzeige. **Seiteneffekte:** ruft `is_available()` (inkl. dessen DB-/Answer-Zugriffe) und `get_description_string()`. **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_JUSTMYALERT]`. **Bewertung:** B — die lokale Initialisierung `$description = ''` wird sofort ueberschrieben (toter Zwischenwert), funktional unkritisch.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid, $subbookingid)` — public
- **Zweck:** No-op — diese Condition hat keine Form-Elemente. **Seiteneffekte:** keine. **Rueckgabe:** void. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $subbookingid, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Baut die Renderdaten fuer eine gruene „bereits gebucht"-Alert-Box (`alert alert-success`, role alert). **Seiteneffekte:** liest global `$USER`; ruft `get_description_string(false, ...)`. **Rueckgabe:** `['mod_booking/bookit_button', $data]` mit `itemid => $settings->id`, `area => 'option'`. **Bewertung:** C — der `$USER`-Fallback ist toter Code: `$userid` hat den Default `0` (nicht `null`), daher greift `if ($userid === null)` nie; ein Aufruf ohne userid liefert `userid => 0` statt der aktuellen User-id. Inkonsistenz, nicht datenschaedigend.

### `public function get_description_string($isavailable, $full, $settings)` — public
- **Zweck:** Liefert den lokalisierten Beschreibungsstring je nach Verfuegbarkeit und Voll-/Studenten-Sicht. **Seiteneffekte:** `get_string(...)`. **Rueckgabe:** string. **Bewertung:** B — Parameter `$settings` wird nicht verwendet (Signatur-Ballast).

### Triviale Properties
`public $id` (Z.50) als hartkodierte Condition-id.

## Bewertungs-Resümee
Standard-Subcondition mit klarer Verfuegbarkeitslogik. Kleinere Schwaechen: der `$USER`-Fallback in `render_button` ist wegen `int $userid = 0`-Default unerreichbar, der toter `$description = ''`-Vorbeleger, ein ungenutzter `$settings`-Parameter und der irrefuehrende „extends" -Klassendoc. Keine funktionalen Daten-/Sicherheitsrisiken. Klassen-Score **B / P3**.
