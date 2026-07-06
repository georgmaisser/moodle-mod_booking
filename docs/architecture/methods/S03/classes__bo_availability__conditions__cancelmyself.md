# cancelmyself — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/cancelmyself.php` · **LOC:** 455 · **Subsystem:** S03 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`cancelmyself` implementiert das Interface `bo_condition` und ist eine hartkodierte Verfuegbarkeits-Bedingung (id `MOD_BOOKING_BO_COND_CANCELMYSELF`), die steuert, ob der Selbst-Stornieren-Button fuer eine Buchungsoption angezeigt wird. Hauptverantwortung liegt in `is_available()`, das eine vielstufige Entscheidungslogik (disablecancel, canceluntil, Wartelisten-/Buchungsstatus, Shopping-Cart-Stornoregeln, Cooling-Off, Slotbooking-Deadlines) buendelt. Kollaborateure: `singleton_service` (booking_answers/settings/user), `booking_option`/`booking` (JSON-Werte, cancel-until), `local_shopping_cart\shopping_cart(_history)`, `slot_change_policy`, `price`, `bo_info` (Button-Render).

## Methoden

### `get_id(): int` — public
- **Zweck:** Liefert die Condition-ID.
- **Rueckgabe:** `int` (`$this->id`). **Seiteneffekte:** keine. **Aufrufkette:** vom bo_availability-Framework. **Bewertung:** A (trivial).

### `is_json_compatible(): bool` — public
- **Zweck:** Markiert die Condition als nicht JSON-konfigurierbar (hartkodiert). **Rueckgabe:** `false`. **Seiteneffekte:** keine. **Bewertung:** A.

### `is_shown_in_mform(): bool` — public
- **Zweck:** Gibt an, dass die Condition nicht im Options-Formular erscheint. **Rueckgabe:** `false`. **Seiteneffekte:** keine. **Bewertung:** A.

### `get_name(): string` — public
- **Zweck:** Lokalisierter Anzeigename. **Rueckgabe:** `get_string('bocondcancelmyself', ...)`. **Seiteneffekte:** keine (Lang-Lookup). **Bewertung:** A.

### `is_skippable(): bool` — public
- **Zweck:** Gibt an, dass die Condition nicht uebersprungen werden darf. **Rueckgabe:** `false`. **Seiteneffekte:** keine. **Bewertung:** A.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernlogik — entscheidet, ob der Stornieren-Button NICHT angezeigt werden soll (`true` = nicht verfuegbar/Button verstecken, invertierte Semantik). Beruecksichtigt globales/options-spezifisches `disablecancel`, `canceluntil`, Aktivitaets-Abschluss, Preis ohne Shopping-Cart, elective/reserved-Status, `cancancelbook`, Wartelisten-/Buchungsstatus, Shopping-Cart-Stornoregeln, Cooling-Off und Slotbooking-Deadlines.
- **Parameter:** `$settings` Options-Settings; `$userid`; `$not` Invertierung.
- **Rueckgabe:** `bool` (invertierte Verfuegbarkeit; `true` = Button verstecken).
- **Seiteneffekte:** Reads ueber `booking_option::get_value_of_json_by_key` / `booking::get_value_of_json_by_key` (JSON-Felder der Option/Instanz, DB-/Cache-gestuetzt), `singleton_service::get_instance_of_booking_answers` (Cache), `get_instance_of_booking_settings_by_cmid`; statische Aufrufe `shopping_cart::allowed_to_cancel_for_item`, `self::has_shopping_cart_history_entry`, `self::apply_coolingoff_period`, `slot_change_policy::answer_all_slots_actionable`. Keine Writes/Events.
- **Aufrufkette:** vom bo_availability-Framework (`bo_info`) und intern von `get_description()`.
- **Bewertung:** **D** — God-Methode: ~118 LOC (113–231), tief verschachtelte if/else-if-Kaskade (bis ~5 Ebenen Zeilen 180–197), gemischte Verantwortungen (Cancel-Policy, Shopping-Cart, Waitinglist, Slotbooking), invertierte `$isavailable`-Semantik mit Kommentaren als einzigem Schutz, redundante `$now = time()`-Zuweisung (Z.116 und 131), JSON-decode inline (Z.191). Hoher Refactoring-Bedarf (Strategy/Guard-Extraktion).

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert optionales Zusatz-SQL zum Ausblenden (hier ungenutzt). **Rueckgabe:** `['', '', '', [], '']`. **Seiteneffekte:** keine. **Bewertung:** A (No-op-Stub).

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Hard-Block-Check; gibt immer `true` zurueck. **Rueckgabe:** `true`. **Seiteneffekte:** keine. **Bewertung:** A (Stub).

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungstext + Prepage/Button-Konstanten fuer die Anzeige. Differenziert Text je nachdem ob Shopping-Cart installiert ist (`'sc cancel'` vs. `get_description_string()`).
- **Rueckgabe:** `[bool $isavailable, string $description, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_CANCEL]`.
- **Seiteneffekte:** ruft `is_available()` (dessen Reads). **Aufrufkette:** vom bo_availability-Rendering. **Bewertung:** B — Beschreibungstext `'sc cancel'` wirkt wie Platzhalter/Magic-String (Z.288), sonst klar.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Interface-Pflicht; fuegt nichts hinzu (hartkodierte Condition). **Seiteneffekte:** keine. **Bewertung:** A (No-op).

### `render_page(int $optionid, int $userid = 0): array` — public
- **Zweck:** Soll optionale Prepage rendern; prueft `alreadybooked`-Verfuegbarkeit, gibt aber in beiden Faellen `[]` zurueck.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings`, instanziiert `alreadybooked` und ruft dessen `is_available()`.
- **Bewertung:** **C** — toter/wirkungsloser Code: der `if`-Zweig (Z.319–322) und der Fall danach liefern beide `[]`; der `alreadybooked`-Check ist folgenlos (cancelmyself.php:314-324). Aufgeblaehter No-op.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Stornieren-Button; unterscheidet Shopping-Cart-Kauf-Storno (eigenes Label/CSS-Klasse) von normaler Stornierung.
- **Parameter:** Settings, userid (Fallback `$USER->id`), full/not/fullwidth.
- **Rueckgabe:** Ergebnis von `bo_info::render_button(...)` (`array`).
- **Seiteneffekte:** liest `global $USER, $PAGE`; `price::get_price`, `get_config('booking','displayemptyprice')`, `singleton_service::get_instance_of_user`/`get_instance_of_booking_answers`, `self::has_shopping_cart_history_entry`; `bo_info::render_button` haengt JS an PAGE-Footer.
- **Aufrufkette:** vom bo_availability-Button-Rendering.
- **Bewertung:** **C** — ~66 LOC, tief geschachtelte Bedingungen (Z.355–390, bis 4 Ebenen), gemischte Preis-/Cart-/Booking-Answer-Logik; `$userid === null`-Guard nach `int`-Typehint mit Default 0 ist toter Branch (Z.349). Duplizierter `render_button`-Aufruf mit nur abweichendem Label/Klasse (cancelmyself.php:340-405).

### `get_description_string(): string` — public
- **Zweck:** Lokalisiertes Label "Stornieren" (cancelsign + cancelmyself). **Rueckgabe:** zusammengesetzter String. **Seiteneffekte:** Lang-Lookups. **Bewertung:** A.

### `has_shopping_cart_history_entry(int $optionid, int $userid): bool` — private static
- **Zweck:** Prueft, ob die Option vom User via local_shopping_cart gekauft wurde (existiert ein History-Eintrag).
- **Seiteneffekte:** `class_exists`-Guard; `shopping_cart_history::get_most_recent_historyitem('mod_booking','option',...)` (Read ueber Shopping-Cart-History-Tabelle).
- **Aufrufkette:** von `is_available()` und `render_button()`. **Bewertung:** A — klar, gekapselt.

### `apply_coolingoff_period($settings, $userid): bool` — public static
- **Zweck:** Gibt `true` zurueck, wenn der User noch innerhalb der konfigurierten Cooling-Off-Periode nach Buchung ist (dann kein Storno).
- **Seiteneffekte:** `get_config('booking','coolingoffperiod')`, `singleton_service::get_instance_of_booking_answers`->`get_users()` (Cache/DB-Read), `strtotime`.
- **Aufrufkette:** von `is_available()` (Z.205). **Bewertung:** B — fehlende Typehints bei Parametern, sonst kompakt und klar.

## Notizen
- Invertierte Semantik (`true` = Button verstecken) durchzieht die Klasse und ist nur per Kommentar dokumentiert — fehleranfaellig.
- `is_available()` ist der dominante Hotspot der Datei und treibt den Klassen-Score.
