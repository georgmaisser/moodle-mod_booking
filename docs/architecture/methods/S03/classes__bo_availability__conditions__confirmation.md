# confirmation — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/confirmation.php` · **LOC:** 271 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`confirmation` ist eine hardcodierte `bo_condition` (id `MOD_BOOKING_BO_COND_CONFIRMATION` = -100) und stellt die finale Abschluss-/Bestaetigungsseite nach erfolgter Buchung dar (POSTBOOK-Prepage). Sie traegt keinen eigenen State und ist nicht JSON-konfigurierbar (`is_json_compatible()` = false, `is_shown_in_mform()` = false). Ihr eigentlicher Mehrwert liegt in `render_page()`, das den Endstatus der Buchung (gebucht / reserviert / Warteliste / noch nicht gebucht) aus den `bo_info`-Condition-Results ermittelt und in das Confirmation-Template uebergibt. Persistenz: keine. Kollaborateure: `bo_info` (Condition-Results), `bookingoption_description` (Output-DTO), `booking_option_settings`, Konstanten aus `lib.php`.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert die hardcodierte Condition-id (`$this->id` = MOD_BOOKING_BO_COND_CONFIRMATION). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Markiert die Condition als nicht JSON-konfigurierbar (hardcoded). **Rueckgabe:** immer false. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Steuert, ob die Condition im Options-mform erscheint. **Rueckgabe:** immer false. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Anzeigename (`bocondconfirmation`). **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Gibt an, ob die Condition uebersprungen werden darf. **Rueckgabe:** immer false. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Verfuegbarkeitscheck. **Seiteneffekte:** keine. **Rueckgabe:** immer false — die Confirmation-Seite ist per Design nie „available" und blockt damit als POSTBOOK-Prepage. **Bewertung:** B — der `$not`-Parameter wird (anders als in den uebrigen Conditions) bewusst ignoriert; korrekt fuer eine reine Abschlussseite, aber inkonsistent zur Schnittstelle.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionales zusaetzliches SQL zum Ausblenden statt Blocken. **Rueckgabe:** Leer-Tupel `['', '', '', [], '']` (keine SQL-Beteiligung). **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block-Check unmittelbar vor der Buchung. **Rueckgabe:** immer false (kein Hard-Block). **Bewertung:** A.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibung + Prepage-Typ + Button-Typ. **Seiteneffekte:** ruft `is_available()`. **Rueckgabe:** `[false, '', MOD_BOOKING_BO_PREPAGE_POSTBOOK, MOD_BOOKING_BO_BUTTON_INDIFFERENT]`. **Bewertung:** B — `$description` wird dreimal hintereinander gesetzt/ueberschrieben (Z.170/175 plus auskommentierte Variante), Endwert immer ''. Toter Zuweisungs-Ballast, funktional unkritisch.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Form-Elemente fuer konfigurierbare Conditions. **Seiteneffekte:** keine (No-op, hardcoded Condition). **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Rendert die Abschlussseite und setzt im `bookingoption_description`-Datensatz ein Status-Flag (`alreadybooked` / `alreadyreserved` / `onwaitinglist` / `notyetbooked`) je nach letztem Condition-Result. **Seiteneffekte:** `$USER`-Fallback fuer `$userid`; `bo_info::get_condition_results($optionid, $userid)`; Instanziierung `bookingoption_description(... MOD_BOOKING_DESCRIPTION_WEBSITE ...)`. **Rueckgabe:** Array mit `data`/`template` (`mod_booking/condition/confirmation`)/`buttontype=1` (Continue-Button deaktiviert). **Bewertung:** B — `array_pop($results)['id']` greift ohne Leer-Pruefung auf das Ergebnis zu; ist `$results` leer, liefert `array_pop(null/[])` null und der Index-Zugriff erzeugt einen Warning/Notice. In der Praxis liefert `get_condition_results` stets mindestens ein Resultat, daher tolerierbar, aber unguarded.

### `public function render_button(...): array` — public
- **Zweck:** Button-Rendering fuer Conditions, die einen Button beisteuern. **Rueckgabe:** leeres Array (diese Condition liefert keinen Button). **Bewertung:** A.

### Triviale Properties
Zwei oeffentliche Properties: `$id` (hardcodierte Condition-id) und `$overwrittenbybillboard` (false, Z.52/55).

## Bewertungs-Resümee
Schlanke, ueberwiegend deklarative Standard-Condition. Der einzige nennenswerte Logikkern ist `render_page()` mit der Status-Ableitung, dort ist der ungesicherte `array_pop(...)['id']`-Zugriff der einzige reale Schwachpunkt. Die mehrfachen toten `$description`-Zuweisungen in `get_description` sind kosmetisch. Klassen-Score **B / P3**.
