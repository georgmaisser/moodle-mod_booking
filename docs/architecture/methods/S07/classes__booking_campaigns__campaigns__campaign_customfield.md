# campaign_customfield — Methoden-Doku
**Datei:** `classes/booking_campaigns/campaigns/campaign_customfield.php` · **LOC:** 438 · **Subsystem:** S07 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S07_*.md)

## Klassenueberblick
Konkrete Kampagnen-Strategie, die `booking_campaign` implementiert: gilt fuer Buchungsoptionen mit einem bestimmten Booking-Option-Customfield. Steuert zeitlich begrenzte Preis-/Limit-Faktoren (`pricefactor`, `limitfactor`), optional user-spezifische Preise ueber Profilfelder. Kollaborateure: `campaigns_info` (Form-Aufbau, Aktiv-/Profilfeld-Checks), `singleton_service` (Booking-Answers), `purge_campaign_caches` (Adhoc-Tasks fuer Cache-Purge), `time_handler`, `booking_option_settings`. Daten werden teils nativ in `booking_campaigns`, teils als JSON-Blob (`json`) persistiert.

## Methoden

### `set_campaigndata(stdClass $record): void` — public
- **Zweck:** Laedt DB-Record-Felder und das JSON-Subobjekt in die Objekt-Properties.
- **Parameter/Rueckgabe:** `$record` = ein `booking_campaigns`-Record; void.
- **Seiteneffekte:** Keine DB/Cache; `json_decode`. Setzt `userspecificprice=true` wenn `cpfield` gesetzt. Normalisiert `cpfield` (array→scalar) und `cpvalue` (scalar→array).
- **Aufrufkette:** Aus `campaigns_info`/Hydration der Kampagnen-Instanzen.
- **Bewertung:** B — sauberes Hydrieren mit Null-Coalescing; leichte Verzweigung fuer Typ-Normalisierung, aber ueberschaubar.

### `add_campaign_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null): void` — public
- **Zweck:** Fuegt die Kampagnen-Formularelemente (Start/Ende, Preis-/Limit-Faktor, Overbooked-Checkbox) hinzu; delegiert Customfield-Teil an `campaigns_info::add_customfields_to_form`.
- **Parameter/Rueckgabe:** mform per Referenz, optional AJAX-Form-Data; void.
- **Seiteneffekte:** `global $DB` deklariert aber **ungenutzt**. Form-Mutation. `get_string`-Calls. `time_handler::set_timeintervall/prettytime`.
- **Aufrufkette:** Form-Building der Campaign-Edit-Form.
- **Bewertung:** B — gerader Form-Aufbau; toter `global $DB` (Smell, campaign_customfield.php:139).

### `get_name_of_campaign_type(bool $localized = true): string` — public
- **Zweck:** Liefert lokalisierten oder rohen Typ-Bezeichner.
- **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `save_campaign(stdClass &$data): void` — public
- **Zweck:** Baut den DB-Record (inkl. JSON-Blob) und persistiert ihn; legt zwei Adhoc-`purge_campaign_caches`-Tasks (Start/Ende) an.
- **Parameter/Rueckgabe:** Form-Data per Referenz; void (setzt `$this->id` bei Insert).
- **Seiteneffekte:** DB-Write `booking_campaigns` (insert_record/update_record). Queued 2 Adhoc-Tasks via `\core\task\manager::queue_adhoc_task`. `json_encode/decode`.
- **Aufrufkette:** Aus Campaign-Edit-Form-Submit.
- **Bewertung:** C — 79 LOC, gemischte Verantwortung (Record-Mapping + JSON-Serialisierung + Task-Orchestrierung). Die zwei Zweige (`limitfactor != 1.0` vs. else) **duplizieren** Insert/Update-Logik und Task-Erzeugung; nur im ersten Zweig erhalten die Tasks `custom_data` — strukturell fragiles Duplikat. Smell: campaign_customfield.php:222-266.

### `set_defaults(stdClass &$data, stdClass $record): void` — public
- **Zweck:** Befuellt Form-Default-Werte aus DB-Record + JSON beim Laden der Edit-Form.
- **Seiteneffekte:** `json_decode`. `switch` auf `$record->type` mit nur einem Case.
- **Aufrufkette:** Form-Initialisierung.
- **Bewertung:** B — funktional; unnoetiges `switch` mit einem Case (leichter Smell).

### `campaign_is_active(int $optionid, booking_option_settings $settings): bool` — public
- **Zweck:** Prueft, ob Kampagne fuer die Option aktuell aktiv ist (Zeitfenster + Customfield-Match).
- **Seiteneffekte:** Delegiert an `campaigns_info::check_if_campaign_is_active`. Mutiert `$this->fieldvalue` (array→scalar reset).
- **Aufrufkette:** Aus Preis-/Limit-Anwendungslogik.
- **Bewertung:** B — `$optionid` ungenutzt; Property-Mutation in einem Check-Getter ist ein leichter Seiteneffekt-Smell, aber begrenzt.

### `get_campaign_price(float $price, int $userid = 0): float` — public
- **Zweck:** Wendet `pricefactor` an, ggf. nur wenn Profilfeld-Bedingung fuer den User zutrifft; rundet abhaengig von `local_shopping_cart`-Config.
- **Seiteneffekte:** `class_exists`/`get_config('local_shopping_cart',...)` (externe Plugin-Kopplung). Delegiert an `campaigns_info::check_if_profilefield_applies`.
- **Aufrufkette:** Preisberechnung der Buchungsoption.
- **Bewertung:** B — klare Logik; harte Abhaengigkeit zu local_shopping_cart per `class_exists` (akzeptabler optionaler Hook).

### `get_campaign_limit(int $limit, booking_option_settings $settings): int` — private
- **Zweck:** Berechnet das angepasste Buchungslimit (limitfactor), beruecksichtigt Overbooking auf Basis vor Kampagnenstart gebuchter User.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_answers` (impliziter DB/Cache-Read der Answers). Schleife ueber `get_usersonlist`.
- **Aufrufkette:** Von `apply_logic`.
- **Bewertung:** B — fokussiert, gut kommentiert; God-Call via singleton_service, aber idiomatisch im Plugin.

### `apply_logic(booking_option_settings &$settings, stdClass &$dbrecord): void` — public
- **Zweck:** Schreibt neues `maxanswers` in DB-Record und Settings.
- **Seiteneffekte:** Mutiert `$settings` und `$dbrecord` per Referenz.
- **Bewertung:** A.

### `is_blocking(booking_option_settings $settings, int $userid): array` — public
- **Zweck:** Interface-Pflicht; diese Kampagne blockiert nie (statisches `status=false`).
- **Bewertung:** A — Stub/No-op, Parameter ungenutzt (Interface-konform).

### Triviale Akzessoren
- `get_name_of_campaign(): string` (Z.417), `get_id_of_campaign(): int` (Z.427), `user_specific_price(): bool` (Z.435) — einfache Property-Getter. **Bewertung:** A.
