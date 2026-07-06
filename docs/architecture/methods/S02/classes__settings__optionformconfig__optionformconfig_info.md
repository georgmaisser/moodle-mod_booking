# optionformconfig_info — Methoden-Doku
**Datei:** `classes/settings/optionformconfig/optionformconfig_info.php` · **LOC:** 411 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_settings.md)

## Klassenueberblick
Statische Utility-Klasse, die die konfigurierbare Sichtbarkeit der Felder im Booking-Optionsformular pro Capability (Experten-/reduzierte Formulare) und pro Kontext verwaltet. Sie liest/schreibt die Konfiguration in der Tabelle `booking_form_config`, merged dynamisch alle registrierten `option\fields`-Klassen (inkl. `bookingextension`-Subplugins) mit gespeicherten Records und liefert UI-Hilfsdaten (Statusmeldung, unchecked Customfields). Kollaborateure: `core_component`/`core_plugin_manager` (Field-Discovery), `context`-Klassen, `$DB`, `html_writer`/`moodle_url` (Meldungsrendering), Webservices (Selektor/Saver). Prozess-lokales Caching ueber `self::$arrayoffieldsets`.

## Methoden

### `destroy_singletons(): void` — public static
- **Zweck:** Setzt den statischen Cache `$arrayoffieldsets` zurueck (Test-/Singleton-Reset).
- **Parameter/Rueckgabe:** keine / void.
- **Seiteneffekte:** Schreibt statisches Property (Prozess-State).
- **Aufrufkette:** Von Test-Setup / Cache-Invalidierung.
- **Bewertung:** A — trivialer Reset.

### `return_configured_fields(int $contextid = 0): array` — public static
- **Zweck:** Liefert fuer alle Capabilities die konfigurierten Feld-Settings; Einstiegspunkt fuer den Webservice/Selektor.
- **Parameter:** `$contextid` (0 ⇒ Systemkontext). **Rueckgabe:** Array von je `{id, capability, json}`.
- **Seiteneffekte:** Indirekt DB-Reads + Cache-Schreiben via `return_configured_fields_for_capability`.
- **Aufrufkette:** Webservice → ruft pro Eintrag in `CAPABILITIES` `return_configured_fields_for_capability`.
- **Bewertung:** A — schlanke Schleife.

### `save_configured_fields(int $contextid, string $capability, string $json): string` — public static
- **Zweck:** Persistiert (delete/update/insert) die JSON-Konfiguration der Formularfelder fuer Kontext+Capability; `reset:true` im JSON loescht den Record.
- **Parameter:** Kontext, Capability, JSON-String. **Rueckgabe:** `'success'`/`'failed'`.
- **Seiteneffekte:** DB-Write `booking_form_config` (delete_records / update_record / insert_record). Cache wird NICHT invalidiert (potenzielle Stale-Daten in `$arrayoffieldsets`).
- **Aufrufkette:** Vom Save-Webservice.
- **Bewertung:** B — klares CUD-Branching; kleiner Smell: `$status='failed'` ist toter Default (jeder Zweig setzt 'success'); kein `destroy_singletons()` nach Write ⇒ Cache-Kohaerenz fragil (optionformconfig_info.php:148-158).

### `return_capability_for_user(int $contextid, int $userid = 0): string` — public static
- **Zweck:** Liefert die erste fuer den User im Kontext gegebene Form-Capability.
- **Parameter:** Kontext, optional User (Default `$USER`). **Rueckgabe:** Capability-String oder `''`.
- **Seiteneffekte:** `has_capability`-Reads; liest `$USER`. Hinweis: `$userid` wird ermittelt, aber bei `has_capability` nicht uebergeben ⇒ prueft immer den aktuellen `$USER`, nicht den uebergebenen User (latenter Bug bei `$userid != $USER->id`).
- **Aufrufkette:** Von `get_unchecked_customfields`, `return_message_stored_optionformconfig`.
- **Bewertung:** C — Parameter `$userid` wirkungslos an `has_capability` (optionformconfig_info.php:179-180).

### `return_configured_fields_for_capability(int $contextid, string $capability): array` — public static
- **Zweck:** Kernmethode: ermittelt das vollstaendige Feldset (alle `option\fields`-Klassen aus mod_booking + bookingextensions), merged es mit dem in DB gespeicherten Record (fehlende Properties aelterer Records nachfuellen) und cached das Ergebnis-JSON.
- **Parameter:** Kontext, Capability. **Rueckgabe:** `{id, capability, json}`.
- **Seiteneffekte:** DB-Read via `return_capabilities_from_db`; `core_component::get_component_classes_in_namespace` + `core_plugin_manager` (Reflection/Plugin-Discovery); statisches String-Building via Field-Klassen-Statics (`$id`, `$fieldcategories`, `get_subfields`); schreibt Cache `$arrayoffieldsets`.
- **Aufrufkette:** Von `return_configured_fields`, `get_unchecked_customfields`.
- **Bewertung:** D — ~92 LOC, mehrere Verantwortlichkeiten (Discovery + Mapping + Merge + Caching), tiefe Schachtelung (Schleife in Merge mit verschachteltem `foreach`/`property_exists`), JSON-Vergleich als Aenderungsdetektion. Aufteilen in collect_fields/merge_with_stored/cache (optionformconfig_info.php:196-287).

### `get_classname(string $context): string` — public static
- **Zweck:** Leitet aus einem voll qualifizierten Klassennamen den lokalisierten Anzeigenamen ab (`get_string(klassenbasis, komponente)`).
- **Parameter:** FQCN-String. **Rueckgabe:** Sprachstring.
- **Seiteneffekte:** `get_string`-Lookup. Fragil: nimmt `array_shift` (erstes Pfadsegment, z. B. `mod_booking`) als Sprachkomponente — bei verschachtelten Namespaces fehleranfaellig.
- **Aufrufkette:** Aus dem Field-Mapping in `return_configured_fields_for_capability`.
- **Bewertung:** C — kryptische String-Manipulation, riskante Komponenten-Annahme (optionformconfig_info.php:294-297).

### `get_unchecked_customfields(int $contextid, int $userid = 0): mixed` — public static
- **Zweck:** Liefert die Shortnames der NICHT aktivierten Customfields fuer Kontext+User.
- **Parameter:** Kontext, optional User. **Rueckgabe:** Array von Shortnames (kann `null`-Eintraege enthalten).
- **Seiteneffekte:** indirekt DB-Reads (Capability + Config).
- **Aufrufkette:** Konsumenten der Formularfilterung.
- **Bewertung:** C — `reset($filteredfields)` ohne Leerheits-Guard ⇒ Notice/Fehler falls Customfields-Feld fehlt; `array_map` kann `null` ins Ergebnis schreiben statt zu filtern (optionformconfig_info.php:318-320).

### `return_message_stored_optionformconfig(int $contextid): string` — public static
- **Zweck:** Erzeugt eine UI-Meldung, auf welcher Kontextebene (System/Coursecat/Course/Module) eine Konfiguration existiert, inkl. Edit-Link.
- **Parameter:** Kontext. **Rueckgabe:** HTML/Sprachstring.
- **Seiteneffekte:** DB-Read via `return_capabilities_from_db`; baut `moodle_url` + `html_writer::link`.
- **Aufrufkette:** Von der optionformconfig-Admin-UI.
- **Bewertung:** C — gemischte Verantwortung (Datenermittlung + HTML-Rendering) gehoert in Renderer; langer switch (optionformconfig_info.php:339-368).

### `return_capabilities_from_db(int $contextid, string $capability): mixed` — private static
- **Zweck:** Holt den am spezifischsten passenden Config-Record entlang des Kontextpfads (vererbte Konfiguration von hoeheren Ebenen).
- **Parameter:** Kontext, Capability. **Rueckgabe:** DB-Record oder false.
- **Seiteneffekte:** Handgebautes SQL mit JOIN auf `{context}`, `get_in_or_equal` ueber alle Pfad-Kontext-IDs, `ORDER BY contextlevel DESC`, `IGNORE_MULTIPLE`.
- **Aufrufkette:** Von `return_configured_fields_for_capability`, `return_message_stored_optionformconfig`.
- **Bewertung:** C — manueller SQL-Bau, aber gut kommentiert und auf parametrisierte Eingaben beschraenkt; Smell durch SQL-in-Info-Klasse (optionformconfig_info.php:399-409).
