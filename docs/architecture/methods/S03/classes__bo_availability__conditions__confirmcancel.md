# confirmcancel — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/confirmcancel.php` · **LOC:** 330 · **Subsystem:** S03 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`confirmcancel` ist eine hartcodierte `bo_condition` (id `MOD_BOOKING_BO_COND_CONFIRMCANCEL`), die den "Wirklich stornieren?"-Bestaetigungs-Schritt beim Abbrechen/Stornieren einer Buchung steuert. Sie blockt regulaer (gibt `is_available=false`), bis der User innerhalb eines kurzen Zeitfensters (`MOD_BOOKING_TIME_TO_CONFIRM`) bereits bestaetigt hat — gespeichert im MUC-Cache `confirmbooking`. Kollaborateure: `singleton_service` (booking_answers), `price`, `bo_info` (Button-Rendering), optional `local_shopping_cart\shopping_cart_history`. Die meisten Interface-Methoden sind triviale No-ops/Konstanten; die einzige nennenswerte Logik steckt in `is_available`.

## Methoden

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Entscheidet, ob der Cancel-Confirm-Schritt aktiv ist. Liefert `false` = blockiert (Bestaetigung noetig), `true` = durchlassen/unsichtbar. Sonderbehandlung bei kostenpflichtigen Optionen (nur auf Warteliste relevant) und Zero-Price.
- **Parameter:** `$settings` Option-Settings, `$userid`, `$not` invertiert das Ergebnis.
- **Rueckgabe:** `bool`.
- **Seiteneffekte:** Liest `singleton_service::get_instance_of_booking_answers` (booking_answers, indirekt DB `booking_answers`); liest `get_config('booking','displayemptyprice')`; MUC-Cache `mod_booking/confirmbooking`: `get($userid)` und ggf. `set($userid, [])` (Cache-Write). Ruft `self::has_shopping_cart_history_entry`.
- **Aufrufkette:** Von `bo_info`/Conditions-Pipeline gerufen; auch intern aus `get_description`.
- **Bewertung:** C — gemischte Verantwortung (Preislogik + Warteliste + Cache-Zeitfenster) in einer Methode, tiefe Verschachtelung (bis 4 Ebenen, Zeile 131-143). **Bug:** Zeile 133 prueft `(float)($price['price'] ?? 0)`, aber `$price` ist in dieser Methode nie definiert → immer leer/0, der `empty(...)`-Zweig greift damit unkonditional sobald `$isavailable` true ist. Wahrscheinlich gemeint: aus `$settings`/`price::` geladener Preis. `confirmcancel.php:133`.

### `has_shopping_cart_history_entry(int $optionid, int $userid): bool` — private static
- **Zweck:** Prueft, ob die Option ueber local_shopping_cart gekauft wurde (juengster History-Eintrag existiert).
- **Rueckgabe:** `bool`.
- **Seiteneffekte:** `class_exists`-Guard; ruft `shopping_cart_history::get_most_recent_historyitem` (DB-Read im Fremdplugin `local_shopping_cart`).
- **Aufrufkette:** Nur aus `is_available`.
- **Bewertung:** A — klein, defensiver Guard fuer optionale Abhaengigkeit.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Interface-Pflicht; diese Condition liefert keine SQL-Filterung.
- **Rueckgabe:** `['', '', '', [], '']` (Leer-Tupel).
- **Seiteneffekte:** keine. **Bewertung:** A (No-op).

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungstext + Prepage/Button-Konstanten fuer die Anzeige.
- **Rueckgabe:** `[bool $isavailable, string $description, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_CANCEL]`.
- **Seiteneffekte:** Ruft `is_available` (inkl. dessen Cache-Reads/Writes) und `get_description_string`.
- **Aufrufkette:** Von Conditions-Pipeline/Renderer.
- **Bewertung:** B — `$description=''` Zeile 245 ist toter Init (sofort ueberschrieben Zeile 249), sonst sauber. Score-Schwelle nicht ueberschritten; minor smell `confirmcancel.php:245`.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den roten Cancel-Button via `bo_info::render_button`.
- **Rueckgabe:** `array` (Template + Daten).
- **Seiteneffekte:** Liest `global $USER`; delegiert an `bo_info::render_button` (haengt JS an Page-Footer).
- **Aufrufkette:** Renderer der Buchungsoptionen.
- **Bewertung:** B — Param-Typ ist `int $userid = 0`, aber Zeile 301 prueft `=== null` (kann bei `int`-Typhint nie eintreten) → toter Branch. `confirmcancel.php:301`.

### `get_description_string(): string` — public
- **Zweck:** Liefert lokalisierten Bestaetigungstext `areyousure:cancel`.
- **Seiteneffekte:** `get_string`. **Bewertung:** A.

### Triviale Akzessoren / No-ops
- `get_id(): int` — gibt `$this->id` zurueck (A).
- `is_json_compatible(): bool` — `false` (A).
- `is_shown_in_mform(): bool` — `false` (A).
- `get_name(): string` — `get_string('bocondconfirmcancel')` (A).
- `is_skippable(): bool` — `false` (A).
- `hard_block(booking_option_settings $settings, $userid): bool` — immer `true` (A).
- `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — leer/Do nothing (A).
- `render_page(int $optionid, int $userid = 0): array` — `[]` (A).
- Properties: `$id`, `$overwrittenbybillboard = false`.
