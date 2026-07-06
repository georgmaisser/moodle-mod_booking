# field_base — Methoden-Doku
**Datei:** `classes/option/field_base.php` · **LOC:** 432 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
Abstrakte Basisklasse fuer die Feld-Plugins der Buchungsoption (`mod_booking\option\fields\*`). Jede konkrete Feld-Klasse repraesentiert eine Property der `booking_option_settings` und liefert die Formularkopplung (Definition, Validation, set_data, prepare_save_field, save_data). Die Basisklasse stellt vor allem leere Default-Hooks (Template-Method-Muster) plus zwei nicht-triviale Helfer (`check_for_changes`, `get_changes_description`) fuer das Change-Tracking, das das `bookingoption_updated`-Event speist. Kollaborateure: `fields_info` (Klassennamen-Aufloesung), `singleton_service` (Settings/User-Lookup), `booking_option_settings`, `MoodleQuickForm`.

## Methoden

### `prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = ''): array` — public static
- **Zweck:** Default-Implementierung, die den Formwert unter dem Feld-Key in `$newoption` uebertraegt (oder `$returnvalue` als Fallback).
- **Parameter/Rueckgabe:** Referenzen auf form-/option-Daten; gibt leeres Array zurueck (Konvention: Changes/Warnings, hier keine).
- **Seiteneffekte:** Mutiert `$newoption->{$key}` (in-memory, kein DB-Write). Liest Klassennamen via `fields_info::get_class_name`.
- **Aufrufkette:** Von `fields_info`/Save-Orchestrierung pro Feld gerufen; von Subklassen ueberschrieben.
- **Bewertung:** A — kurz, klar, sinnvoller generischer Default.

### `instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Leerer Hook fuer das Hinzufuegen der Formularelemente; Subklassen ueberschreiben.
- **Seiteneffekte:** Keine (No-op).
- **Aufrufkette:** Vom Optionsformular-Aufbau gerufen.
- **Bewertung:** A — bewusster leerer Template-Hook.

### `validation(array $data, array $files, array &$errors): array` — public static
- **Zweck:** Default-Validierung; gibt `$errors` unveraendert zurueck.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Aus Form-`validation()` pro Feld.
- **Bewertung:** A — trivialer Default.

### `save_data(stdClass &$formdata, stdClass &$option)` — public static
- **Zweck:** Leerer Hook fuer Post-Save-Persistenz (Felder, die die Option-ID brauchen, z.B. eigene Tabellen).
- **Seiteneffekte:** Keine in Basis; Subklassen schreiben DB.
- **Aufrufkette:** Nach dem Speichern der Option gerufen (MOD_BOOKING_EXECUTION-Phasen via `$save`).
- **Bewertung:** A — leerer Template-Hook.

### `set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Uebertraegt den gespeicherten Wert aus `$settings` ins Formular-`$data`-Objekt, idempotent (kein Ueberschreiben falls bereits gesetzt).
- **Seiteneffekte:** Mutiert `$data->{$key}`; liest Klassennamen via `fields_info`.
- **Aufrufkette:** Aus Form-`set_data`/`check_for_changes`; von Subklassen ueberschrieben.
- **Bewertung:** A — kompakt, sinnvoller Default.

### `definition_after_data(MoodleQuickForm &$mform, $formdata)` — public static
- **Zweck:** Leerer `definition_after_data`-Hook.
- **Seiteneffekte:** Keine.
- **Bewertung:** A — leerer Template-Hook.

### `changes_collected_action(array $changes, object $data, object $newoption, object $originaloption)` — public static
- **Zweck:** Leerer Hook, der nach dem Einsammeln aller Changes ausgeloest wird (Felder koennen reagieren).
- **Seiteneffekte:** Keine in Basis.
- **Bewertung:** A — leerer Template-Hook.

### `check_for_changes(stdClass $formdata, field_base $self, $mockdata = '', ?string $key = null, $value = ''): array` — public
- **Zweck:** Ermittelt, ob sich der alte (per `set_data` aus den Settings rekonstruierte) und der neue Formwert eines Feldes unterscheiden, und liefert ggf. einen Change-Record.
- **Parameter/Rueckgabe:** `$formdata` (Form), `$self` (Feld-Instanz), optional `$mockdata`/`$key`/`$value`; gibt `['changes' => [...]]` oder `[]`.
- **Seiteneffekte:** Liest Option-Settings via `singleton_service::get_instance_of_booking_option_settings($formdata->id)` (gecached/DB); ruft `$self::set_data` auf `$mockdata` auf (mutiert lokales Objekt). Nutzt globale Konstante `MOD_BOOKING_CLASSES_EXCLUDED_FROM_CHANGES_TRACKING`.
- **Aufrufkette:** Aus der Save-Orchestrierung (fields_info) pro Feld; ruft `set_data` der Subklasse.
- **Bewertung:** C — ~85 LOC, hohe zyklomatische Komplexitaet durch dreifach verschachtelte Typ-Fallunterscheidung (array['text'] / object->text / default) jeweils fuer old- und newvalue praktisch dupliziert (`field_base.php:264-302`). Wertgleichheits-Check mit loser `!=`-Vergleich plus Empty-Sonderregel (`field_base.php:304-307`) ist fragil. Parameter `$self` ist redundant (statischer Kontext `static::` vorhanden) und wird sogar mit `!isset` geprueft, obwohl als `field_base` typisiert (`field_base.php:241`) — toter Guard.

### `get_changes_description(array $changes): array` — public
- **Zweck:** Bereitet einen Change-Record fuer das `bookingoption_updated`-Event menschenlesbar auf (User-IDs → Namen, Timestamps → Datum, 1/0 → on/off, Feldname → Sprachstring).
- **Parameter/Rueckgabe:** `$changes` (oldvalue/newvalue/fieldname); gibt aufbereitetes Array (`oldvalue`, `newvalue`, `fieldname`/`info`).
- **Seiteneffekte:** `get_string`-Lookups; `userdate`-Formatierung; ruft `resolve_userid_as_readable_personparams` (User-Lookup via singleton_service).
- **Aufrufkette:** Aus dem Event-/Logging-Pfad nach Save.
- **Bewertung:** C — ~83 LOC mit hartcodierten Feldnamen-Listen (`areaswithuseridstoresolve`, `areaswithtimestampstoresolve`, `checkboxvalues`, `field_base.php:343-361`). Diese feld-spezifische Konfiguration gehoert in die jeweiligen Subklassen statt in die Basisklasse (gemischte Verantwortung / verletzt Open-Closed). User-/Array-Aufloesungsbloecke fuer old/new sind dupliziert (`field_base.php:373-396`). `$ov`/`$nv` werden gesetzt aber Ergebnis pro Iteration ueberschrieben (Schleife sammelt via Referenz `$returnvalue`, Rueckgabe nur des letzten) — funktioniert, aber unsauber.

### `resolve_userid_as_readable_personparams(int $userid, string &$returnvalue): bool` — private
- **Zweck:** Haengt die lesbare Userinfo eines Users an `$returnvalue` an (komma-separiert).
- **Seiteneffekte:** `singleton_service::get_instance_of_user` (gecached/DB); `get_string('userinfosasstring')`.
- **Aufrufkette:** Nur aus `get_changes_description`.
- **Bewertung:** B — klein und fokussiert; leichter Smell durch Akkumulation per Referenz-Parameter statt Rueckgabewert (Bool-Rueckgabe + String-Mutation gemischt).

### Triviale Akzessoren
- `return_classname_name(): string` — public static: letzter Namespace-Teil des Called-Class (explode/array_pop). Score A.
- `return_full_classname(): string` — public static: `get_called_class()`. Score A.
- `get_subfields(): array` — public static: gibt `[]` (Default, ueberschreibbar). Score A.
- Statische Properties `$id`, `$save`, `$header`, `$incompatiblefields`: Default-Metadaten fuer Sortierung/Save-Phase/Header/Inkompatibilitaeten.
