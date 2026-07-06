# notifymelist — Methoden-Doku

**Datei:** `classes/bo_availability/conditions/notifymelist.php` · **LOC:** 329 · **Subsystem:** S03 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`notifymelist` implementiert das Interface `bo_condition` als hartkodierte (nicht JSON-konfigurierbare, nicht im mform sichtbare) Verfuegbarkeits-Bedingung mit fester id `MOD_BOOKING_BO_COND_NOTIFYMELIST`. Sie steuert, ob fuer eine ausgebuchte Buchungsoption der "Benachrichtige mich"-Button (Warteliste/Notify-Liste) angezeigt wird. Hauptkollaborateure: `singleton_service` (Booking-Answers), `button_notifyme` (Output), `bo_info` (Billboard), Moodle-Capabilities/Config. Die Klasse ist ueberwiegend Boilerplate-Implementierung der Interface-Methoden; die eigentliche Logik steckt in `is_available()`.

## Methoden

### `get_id(): int` — public
- **Zweck:** Liefert die hartkodierte Condition-id.
- **Rueckgabe:** int (`$this->id`).
- **Seiteneffekte:** keine.
- **Aufrufkette:** Vom `bo_info`/Condition-Dispatcher.
- **Bewertung:** A — trivialer Getter.

### `is_json_compatible(): bool` — public
- **Zweck:** Markiert Condition als nicht JSON-faehig (hartkodiert).
- **Rueckgabe:** `false`.
- **Seiteneffekte:** keine. **Bewertung:** A.

### `is_shown_in_mform(): bool` — public
- **Zweck:** Steuert, ob Condition im Options-Formular erscheint.
- **Rueckgabe:** `false`.
- **Seiteneffekte:** keine. **Bewertung:** A.

### `get_name(): string` — public
- **Zweck:** Lokalisierter Anzeigename.
- **Rueckgabe:** `get_string('bocondnotifymelist', 'mod_booking')`.
- **Seiteneffekte:** keine (Sprach-String-Lookup). **Bewertung:** A.

### `is_skippable(): bool` — public
- **Zweck:** Ob die Bedingung uebersprungen werden darf.
- **Rueckgabe:** `false`.
- **Seiteneffekte:** keine. **Bewertung:** A.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernlogik — entscheidet, ob die Option fuer den User "verfuegbar" ist im Sinne der Notify-Liste (true = kein Notify-Button noetig / Buchung moeglich; false = ausgebucht, Notify-Button greift).
- **Parameter:** `$settings` Option-Settings, `$userid` Zieluser, `$not` invertiert Ergebnis.
- **Rueckgabe:** bool.
- **Seiteneffekte:** Liest `get_config('booking','usenotificationlist')` und `turnoffwaitinglist`; `has_capability('local/shopping_cart:cashier', context_system)`; `class_exists`/`isloggedin`/`isguestuser`; nutzt `singleton_service::get_instance_of_booking_answers()` → indirekt DB/Cache-Reads ueber Booking-Answers (`return_all_booking_information`, `get_usersonwaitinglist`). Globals: `$USER` deklariert aber faktisch ungenutzt (Zeile 116). Keine Writes.
- **Aufrufkette:** Vom Condition-Resolver in `bo_info`; intern von `get_description()` (Zeile 229) aufgerufen.
- **Bewertung:** C — ~62 LOC, hohe zyklomatische Komplexitaet durch tief verschachtelte if/else-if-Kette (notifymelist.php:125-168), gemischte Verantwortung (Config-Lese-Gating + Cashier-Capability + Warteliste-Logik). `global $USER` wird deklariert, aber nie verwendet (notifymelist.php:116) — toter Code. Frueher `return true` (Zeile 148) durchbricht die `$not`-Inversion am Ende — Inkonsistenz: bei `iambooked` wird der invertierte Pfad nie erreicht (potentieller Logik-Smell notifymelist.php:147-149).

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionaler SQL-Zusatz zum Ausblenden von Optionen — hier leer.
- **Rueckgabe:** `['', '', '', [], '']`.
- **Seiteneffekte:** keine. **Bewertung:** A — No-op-Implementierung des Interface-Vertrags.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Buchungssperre kurz vor Buchung — hier immer `false` (Notify-Liste blockt nie hart).
- **Rueckgabe:** `false`.
- **Seiteneffekte:** keine. **Bewertung:** A.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungstext + Prepage/Button-Typ.
- **Rueckgabe:** `[bool $isavailable, string $description, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_JUSTMYALERT]`.
- **Seiteneffekte:** ruft `is_available()` (transitive Config/DB-Reads) und `get_description_string()`.
- **Aufrufkette:** Vom `bo_info`-Beschreibungsaggregator. **Bewertung:** A — knappe Delegation.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Form-Elemente — hier leer (hartkodierte Condition).
- **Rueckgabe:** void. **Seiteneffekte:** keine. **Bewertung:** A — No-op.

### `render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Optionale Prepage — hier keine.
- **Rueckgabe:** `[]`. **Seiteneffekte:** keine. **Bewertung:** A — No-op.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Baut den "Notify me"-Button samt Template-Daten.
- **Parameter:** $settings, $userid (Default aktueller $USER), restliche aus Interface-Signatur (full/not/fullwidth ungenutzt).
- **Rueckgabe:** `['mod_booking/button_notifyme', $notifyme->return_as_array()]`.
- **Seiteneffekte:** Global `$USER` (Default-User); `singleton_service::get_instance_of_booking_answers()` → DB/Cache-Read via `return_all_booking_information`; instanziiert `button_notifyme`.
- **Aufrufkette:** Vom Button-Renderer in `bo_info`. **Bewertung:** B — kompakt, aber statischer Singleton-God-Call und ungenutzte Interface-Parameter (full/not/fullwidth, notifymelist.php:273-279).

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings): string` — public
- **Zweck:** Liefert lokalisierten Beschreibungstext (Billboard-Override oder fully-booked-Varianten je nach $full/$isavailable).
- **Rueckgabe:** string (Sprach-String oder Billboard-Text).
- **Seiteneffekte:** `bo_info::apply_billboard()` (statischer Call, nur wenn `overwrittenbybillboard` — hier konstant false, daher Billboard-Zweig toter Pfad). Sprach-String-Lookups.
- **Aufrufkette:** von `get_description()`.
- **Bewertung:** B — klar, aber Billboard-Zweig (notifymelist.php:313-319) ist wegen `$overwrittenbybillboard = false` faktisch unerreichbar; Inline-Zuweisung in `!empty($desc = ...)` mindert Lesbarkeit.

## Triviale Akzessoren / Properties
- `$id`, `$overridable = true`, `$overwrittenbybillboard = false` — oeffentliche Property-Defaults, keine Logik.
