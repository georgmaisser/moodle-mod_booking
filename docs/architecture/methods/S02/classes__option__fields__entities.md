# entities — Methoden-Doku
**Datei:** `classes/option/fields/entities.php` · **LOC:** 389 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`entities` ist ein `field_base`-Subfeld der Optionsformular-Pipeline (`fields_info`) und kapselt die Anbindung des optionalen Plugins `local_entities` (Orte/Ressourcen) an eine Buchungsoption. Es delegiert nahezu alle echte Arbeit an `entitiesrelation_handler` (Formularaufbau, Validierung, Persistenz) und ist selbst nur Adapter/Glue: Es spiegelt den Entity-Namen/-Adresse in `location`/`address` der Option, baut `entitydate`-Kandidaten fuer Belegungskonflikte und bereitet Change-Tracking-Eintraege fuer das `bookingoption_updated`-Event auf. Alle Pfade sind durch `class_exists('local_entities\...')`-Guards no-op, wenn das Plugin fehlt. Kollaborateure: `entitiesrelation_handler`, `entitydate`, `dates`, `singleton_service`, `booking_option_settings`.

## Methoden

### `public static prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Interpretiert die Entity-Auswahl aus dem Formular und schreibt Name/Adresse der gewaehlten Entity in `$newoption->location`/`->address`, damit Filter/Anzeige ohne Join arbeiten koennen.
- **Parameter/Rueckgabe:** Mutiert `$newoption` per Referenz (location/address). Gibt immer `[]` zurueck (kein Warntext). Signatur deklariert `array`, PHPDoc sagt `string` — inkonsistent.
- **Seiteneffekte:** Liest via statischer `entitiesrelation_handler::get_name_for_filter` / `::get_first_address_as_string` (intern DB-Reads). Keine Writes hier. Mutiert `$formdata` per Referenz im (toten) Optiondate-Zweig.
- **Aufrufkette:** Von `fields_info`/Optionsspeicher-Pipeline (presave). Ruft statische Handler-Methoden.
- **Bewertung:** C — Zeile 125 `if (!empty($option))`: Variable `$option` ist in diesem Scope nie definiert, der gesamte foreach-Block (123-136) ist toter Code. Gemischte Verantwortung (location/address + simulierte Optiondate-Spiegelung) + statische God-Calls. Smell: `entities.php:125` (dead code, undefined `$option`).

### `public static instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Haengt den Entity-Auswahlblock von `entitiesrelation_handler` ins Optionsformular ein.
- **Parameter/Rueckgabe:** `void`. Mutiert `$mform` per Referenz.
- **Seiteneffekte:** Delegiert komplett an `$erhandler->instance_form_definition($mform, 0)`. Keine DB/Cache direkt.
- **Aufrufkette:** Vom Formularaufbau der Optionsmaske.
- **Bewertung:** B — schlanker Adapter, aber ~15 Zeilen auskommentierter Altcode (`er_saverelationsforoptiondates`/confirm-Box, 168-183) als Rauschen.

### `public static validation(array $data, array $files, array &$errors)` — public static
- **Zweck:** Sammelt Validierungsfehler des Entity-Blocks; vorab werden die Formulardaten via `order_all_dates_to_book_in_form` mit `datestobook` angereichert, damit der Handler Belegungskonflikte prüfen kann.
- **Parameter/Rueckgabe:** `void`. Fuellt `$errors` per Referenz.
- **Seiteneffekte:** Erzeugt `entitiesrelation_handler(... , $data['id'])`; `instance_form_validation` liest DB (andere Buchungen mit gleicher Entity).
- **Aufrufkette:** Von der Optionsformular-Validierung. Ruft `self::order_all_dates_to_book_in_form`.
- **Bewertung:** B — kurz und klar; Array/Objekt-Hin-und-Her (`$fromform = (object)$data` … `(array)$fromform`) ist framework-bedingt.

### `public static order_all_dates_to_book_in_form(stdClass &$fromform): void` — public static
- **Zweck:** Baut aus den im Formular eingereichten Terminen `entitydate`-Objekte und legt sie in `$fromform->datestobook` ab; diese Kandidaten werden gegen alle Buchungen derselben Entity auf zeitliche Ueberschneidung geprueft.
- **Parameter/Rueckgabe:** `void`. Mutiert `$fromform->datestobook` per Referenz.
- **Seiteneffekte:** Liest Termine via `dates::get_list_of_submitted_dates`; erzeugt `moodle_url` (stabiler Self-Link, damit eigene Dates beim Konflikt-Check uebersprungen werden). Keine DB-Writes.
- **Aufrufkette:** Von `validation()`.
- **Bewertung:** B — ~44 LOC, aber klar strukturiert und gut kommentiert; einzige Verantwortung (DTO-Aufbau).

### `public static save_data(stdClass &$formdata, stdClass &$option, int $index = 0): array` — public static
- **Zweck:** Postsave-Persistenz der Entity-Relation (braucht Option-ID) und Aufbau eines Change-Eintrags fuer das `bookingoption_updated`-Event, falls sich die Entity geaendert hat.
- **Parameter/Rueckgabe:** Gibt `[]` oder `['changes' => [...]]` (fieldname/oldvalue/newvalue) zurueck.
- **Seiteneffekte:** `$erhandler->instance_form_save(...)` schreibt in `local_entities`-Relationstabelle (DB-Write). Liest alten Stand via `singleton_service::get_instance_of_booking_option_settings` und neue Entity via `singleton_service::get_entity_by_id`.
- **Aufrufkette:** Vom Postsave-Schritt der Speicher-Pipeline. Ruft statische Singleton-Services.
- **Bewertung:** C — verschachtelte 3-fach-OR-Aenderungserkennung (292-296) ist schwer lesbar/fehleranfaellig; gemischte Verantwortung (Persistenz + Change-Diff); statische God-Calls (`singleton_service`). Smell: `entities.php:292` (komplexe Diff-Bedingung).

### `public static set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Ueberfuehrt gespeicherte/importierte Entity-Werte ins Formular: Normalfall delegiert an `values_for_set_data`; beim Import wird die Entity per ID, Name oder Shortname aufgeloest.
- **Parameter/Rueckgabe:** `void`. Mutiert `$data` (location, `local_entities_entityid_0`, `er_saverelationsforoptiondates`).
- **Seiteneffekte:** DB-Reads via `$erhandler->get_entities_by_id/_by_name/_by_shortname` und `values_for_set_data`.
- **Aufrufkette:** Vom Formular-Prefill (`set_data`-Phase). Parameter `$settings` wird nicht genutzt.
- **Bewertung:** C — drei Aufloesungsstrategien (A/B/C) mit early-return im else-Zweig vermischen Import- und Normalpfad in einer Methode; ungenutzter Parameter `$settings`. Smell: `entities.php:337` (verzweigte gemischte Verantwortung Import/Normal).

### `public get_changes_description(array $changes): array` — public
- **Zweck:** Formatiert einen Entity-Aenderungseintrag in lokalisierte old/new/info-Strings fuer die Event-/Anzeigeschicht.
- **Parameter/Rueckgabe:** Gibt Array mit `oldvalue`/`newvalue`/`fieldname` (+ ggf. `info`) zurueck.
- **Seiteneffekte:** Nur `get_string` (i18n). Keine DB/Cache.
- **Aufrufkette:** Von der Change-Tracking-/Event-Beschreibungsschicht (Konsument von `save_data`-Output).
- **Bewertung:** B — kurz; einzige Inkonsistenz: Strings teils unter Component `'booking'`, teils `'mod_booking'`.

## Anmerkungen
- Diese Methode ist die einzige nicht-statische (Instanzmethode `get_changes_description`); alle uebrigen sind static — gemischtes Aufrufmodell aus `field_base`.
- Wiederkehrender `class_exists('local_entities\...')`-Guard in 5 Methoden (Kopplung an optionales Plugin als String-Check).
