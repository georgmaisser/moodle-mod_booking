# bookwithsubscription — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/bookwithsubscription.php` · **LOC:** 360 · **Subsystem:** S03 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S03_*.md)

## Klassenueberblick
`bookwithsubscription` implementiert das Interface `bo_condition` und ist eine hartkodierte Availability-Condition (ID `MOD_BOOKING_BO_COND_BOOKWITHSUBSCRIPTION`) in der Booking-Conditions-Kette. Sie ist eine der "letzten" Conditions, die statt zu blockieren den eigentlichen Buchungs-/Bookit-Button rendert (Credit-/Subscription-basierte Buchung). Kollaborateure: `bo_info` (Button-Rendering, Billboard), `booking_bookit` (Bookit-Template), `singleton_service` (User/Settings/BookingAnswers), `bookingoption_description` (Output) sowie `get_config('booking', ...)` fuer Credit-Einstellungen.

## Methoden

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Soll pruefen, ob die Option (credit-/subscriptionbasiert) verfuegbar ist.
- **Parameter / Rueckgabe:** Settings, userid, Invert-Flag → bool.
- **Seiteneffekte:** `get_config('booking', ...)` (Reads), `singleton_service::get_instance_of_user` + `profile_load_custom_fields` (DB-Read Profilfelder) — aber NUR im toten Code unterhalb `return true;`.
- **Aufrufkette:** Von `get_description()` (Z.203) gerufen; Teil der bo_condition-Kette via `bo_info`.
- **Bewertung:** **D** — `return true;` in Z.121 macht die gesamte Credit-Logik (Z.123-149) zu totem Code (`bookwithsubscription.php:121`). Gemischte Verantwortung + statischer God-Call auf `get_config`/`singleton_service`. Funktional ist die Methode auf "immer verfuegbar" reduziert.

### `render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Baut die Prepage-Modal-Daten (Optionsbeschreibung + Bookit-Button) fuer die Buchungsstrecke.
- **Parameter / Rueckgabe:** optionid, userid → Array `['template','buttontype','data']`.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` + `get_instance_of_booking_answers` (DB-Read via Singleton-Cache), `booking_bookit::render_bookit_template_data` (Template-Render). Keine Writes.
- **Aufrufkette:** Von der Prepage-Modal-Logik (`bo_info`/booking_bookit Flow) gerufen.
- **Bewertung:** **C** — ~46 LOC mit auskommentiertem Buttontype-Block (Z.260-264, 269) → toter/halbgarer Code; `$dataarray`/`$templates` ohne Init genutzt; mehrfache `reset()`-Tricks auf Render-Rueckgabe sind fragil (`bookwithsubscription.php:244`).

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Buchungs-Button mit Credit-abhaengigem Label und delegiert an `bo_info::render_button`.
- **Parameter / Rueckgabe:** Settings + Flags → Array (Template + Daten).
- **Seiteneffekte:** `global $USER`, `singleton_service::get_instance_of_user` (DB-Read), `get_config('booking','bookwithcreditsprofilefield')` (Read), `bo_info::render_button` (Render).
- **Aufrufkette:** Von der Button-Render-Stufe der Condition-Kette.
- **Bewertung:** **C** — `$userid === null`-Guard (Z.301) ist toter Pfad (Param ist `int` mit Default `0`, nie null); `$user->profile[$profilefield]` setzt eine andere Datenstruktur voraus als `is_available` (`$user->{"profile_field_".$profilefield}`) → inkonsistente Credit-Lesart, potenzieller Bug (`bookwithsubscription.php:312`).

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungsstring + Prepage-/Button-Konstanten.
- **Rueckgabe:** `[bool $isavailable, string $description, MOD_BOOKING_BO_PREPAGE_BOOK, MOD_BOOKING_BO_BUTTON_MYBUTTON]`.
- **Seiteneffekte:** ruft `is_available()` und `get_description_string()`.
- **Aufrufkette:** Standard bo_condition-Schnittstelle, von `bo_info` aggregiert.
- **Bewertung:** **B** — schlank, klar.

### `get_description_string($isavailable, $full, $settings): string` — public
- **Zweck:** Liefert lokalisierten Beschreibungs-/Billboard-String; im Normalfall `booknow`.
- **Seiteneffekte:** `bo_info::apply_billboard` (potenziell Config/Render), `get_string`.
- **Aufrufkette:** von `get_description()`.
- **Bewertung:** **B** — kompakt; Assignment-in-Condition (`!empty($desc = ...)`, Z.349) leicht trickreich, aber vertretbar.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionaler SQL-Beitrag der Condition; hier leer (kein Hide-Filter).
- **Rueckgabe:** `['', '', '', [], '']`.
- **Bewertung:** **A** — trivialer No-op-Contract.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Hard-Block-Komplement; gibt immer `true` (Buchung wird hier nicht verhindert — Block bedeutet "Button zeigen").
- **Bewertung:** **A** — trivial.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op (hartkodierte Condition, keine Formularelemente).
- **Bewertung:** **A** — leer per Design.

### Triviale Akzessoren / Konstanten-Rueckgaben
- `get_id(): int` — public — gibt `$this->id` zurueck.
- `is_json_compatible(): bool` — public — `false` (hartkodiert).
- `is_shown_in_mform(): bool` — public — `false`.
- `get_name(): string` — public — `get_string('bocondbookwithsubscription', ...)`.
- `is_skippable(): bool` — public — `false`.
- **Bewertung:** **A** — Interface-Boilerplate, je 1-3 Zeilen.
