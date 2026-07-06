# actions_info — Methoden-Doku
**Datei:** `classes/bo_actions/actions_info.php` · **LOC:** 350 · **Subsystem:** S04 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S04_bo_actions.md)

## Klassenueberblick
Statische Fassade/Registry fuer "Booking Actions" (typgesteuerte Aktionen, die bei Buchung oder Stornierung einer Buchungsoption ausgefuehrt werden). Die Klasse entdeckt Action-Typ-Klassen per `core_component`-Namespace-Scan, instanziiert sie, haengt deren mform-Elemente in das Options-Formular ein und delegiert Speichern/Loeschen/Ausfuehren an die jeweilige Action-Typ-Klasse. Hauptkollaborateure: `bo_actions\action_types\*` (eigentliche Action-Implementierungen), `booking_option` (DB-Persistenz via `update()`/JSON-Helfer), `booking_option_settings` + `singleton_service` (Settings-Cache), `output\actionslist` (Renderer), `wb_payment` (PRO-Gate). Reine prozedurale Statik ohne Zustand — Single-Responsibility ist "Vermittlung zwischen Formular/Engine und Action-Typen", weitgehend eingehalten.

## Methoden

### `add_actions_to_mform(MoodleQuickForm &$mform, array &$formdata = []): void` — public static
- **Zweck:** Haengt den Action-Header und (bei gespeicherter Option) die Liste vorhandener Actions plus verstecktes `boactionsjson`-Feld an das Options-mform; sonst Hinweis "nur bei gespeicherter Option".
- **Parameter/Rueckgabe:** mform per Referenz, Formdata-Array; kein Returnwert.
- **Seiteneffekte:** Liest Config `showboactions` (get_config) + PRO-Status (`wb_payment::pro_version_is_activated`). Mutiert das mform-Objekt. Keine DB-Writes direkt.
- **Aufrufkette:** Wird vom Options-Formular-Aufbau (edit_options/option-form) gerufen; ruft `add_list_of_existing_actions_for_this_option`.
- **Bewertung:** B — klar abgegrenzt; doppelte Gate-Bedingung (Config + PRO) inline, aber vertretbar.

### `add_actionsform_to_mform(MoodleQuickForm &$mform, array &$formdata = []): void` — public static
- **Zweck:** Duenne Weiterleitung an `add_action` (Modal-Formular fuer Typauswahl).
- **Seiteneffekte:** keine direkt; delegiert.
- **Aufrufkette:** Vom Modal-/Edit-Action-Formular; ruft `add_action`.
- **Bewertung:** B — reiner Delegations-Wrapper; koennte entfallen, schadet aber nicht.

### `get_action_types(): array` — public static
- **Zweck:** Entdeckt alle Action-Typ-Klassen im Namespace `bo_actions\action_types` und liefert je eine Instanz.
- **Rueckgabe:** Array von Action-Instanzen.
- **Seiteneffekte:** `core_component::get_component_classes_in_namespace` (Klassen-Scan); instanziiert jede Klasse (Konstruktor-Seiteneffekte je Action moeglich).
- **Aufrufkette:** Von `add_action`; extern fuer Auswahllisten.
- **Bewertung:** B — sauber; instanziiert alle Typen nur um Metadaten zu lesen (kleiner Overhead, gewollt).

### `get_action(string $actiontype): mixed` — public static
- **Zweck:** Factory — instanziiert eine Action-Typ-Klasse per Kurznamen; `null` falls Klasse fehlt.
- **Seiteneffekte:** `global $CFG` deklariert aber ungenutzt; `class_exists`-Check.
- **Aufrufkette:** Von `set_data_for_form`, `save_action`, `add_action`; zentrale Factory.
- **Bewertung:** B — funktional; ungenutztes `global $CFG` (actions_info.php:125) ist toter Import. Rueckgabetyp `mixed` unpraezise.

### `set_data_for_form(object &$data): object` — public static
- **Zweck:** Laedt fuer das Edit-Formular die Defaults der zu bearbeitenden Action aus den Option-Settings und delegiert an deren `set_defaults`.
- **Seiteneffekte:** `global $DB` deklariert aber ungenutzt; liest Settings via `singleton_service::get_instance_of_booking_option_settings` (Cache/DB).
- **Aufrufkette:** Vom Action-Edit-DynamicForm; ruft `get_action` + `$action->set_defaults`.
- **Bewertung:** C — moeglicher Null-Deref: wenn `$settings->boactions[$data->id]` leer ist, bleibt `$action` undefiniert/null und `$action->set_defaults(...)` (actions_info.php:160) wuerde fatalen Fehler werfen; der if-Guard schuetzt die Zuweisung, nicht die Nutzung. Ungenutztes `global $DB` (actions_info.php:145).

### `save_action(stdClass &$data): void` — public static
- **Zweck:** Instanziiert den passenden Action-Typ und delegiert das Persistieren an dessen `save_action`.
- **Seiteneffekte:** Indirekt DB-Write durch die Action-Klasse (schreibt in Option-JSON / booking_options).
- **Aufrufkette:** Vom Action-Speichern-Pfad; ruft `get_action` + `$action->save_action`.
- **Bewertung:** B — schlanke Delegation; kein Null-Check auf `get_action`-Ergebnis (theoretischer Null-Deref bei unbekanntem Typ).

### `delete_action(stdClass $data): void` — public static
- **Zweck:** Entfernt eine Action aus dem `boactions`-JSON der Option und persistiert via `booking_option::update`, loest Update-Event aus.
- **Seiteneffekte:** `global $USER`; liest Settings (singleton); **DB-Write** ueber `booking_option::update(...MOD_BOOKING_UPDATE_OPTIONS_PARAM_REDUCED)` (Tabelle booking_options, JSON-Feld); `context_module::instance`; feuert `booking_option::trigger_updated_event(... 'actions')`. Implizit Cache-Purge ueber update().
- **Aufrufkette:** Vom Action-Loeschen-Button/Service.
- **Bewertung:** C — gemischte Verantwortung (JSON-Manipulation + Persistenz + Event in einer Methode); offenes "Todo: Actually delete information from option" (actions_info.php:187) signalisiert unfertige/duplizierte Loeschlogik (unset auf jsonobject UND boactions). Kein Returnstatus.

### `add_list_of_existing_actions_for_this_option(MoodleQuickForm &$mform, array &$formdata = []): void` — private static
- **Zweck:** Rendert die Liste bestehender Actions (mit Edit/Delete-No-Submit-Buttons) als HTML in das mform.
- **Seiteneffekte:** `global $DB` (ungenutzt), `global $PAGE`; liest JSON via `booking_option::get_value_of_json_by_key`; nutzt Renderer `render_boactionslist`; mutiert mform.
- **Aufrufkette:** Von `add_actions_to_mform`.
- **Bewertung:** B — klar; ungenutztes `global $DB` (actions_info.php:219).

### `add_action(MoodleQuickForm &$mform, array &$formdata): void` — public static
- **Zweck:** Baut den Typ-Selektor (No-Submit-Reload-Button) auf, fuegt die typspezifischen mform-Elemente der gewaehlten Action hinzu und das typ-agnostische `boactiononcancel`-Flag.
- **Seiteneffekte:** Mutiert mform (Group/Select/Submit/advcheckbox); kein DB.
- **Aufrufkette:** Von `add_actionsform_to_mform`; ruft `get_action_types`, `get_action`, `$action->add_action_to_mform`.
- **Bewertung:** B — ~50 LOC, aber kohaerent (Formularaufbau); kein Null-Check auf `get_action`-Resultat vor `add_action_to_mform`.

### `apply_actions(booking_option_settings $settings, int $userid = 0, string $trigger = 'book', int $baid = 0): int` — public static
- **Zweck:** Fuehrt zur Laufzeit alle passenden Actions einer Option aus; Trigger-Gate trennt 'book'- von 'cancel'-Actions; aggregiert Status.
- **Rueckgabe:** int Status (0 = nichts, 1 = sofort abbrechen). **Bug-relevant:** gibt `$status` (letzte Iteration) statt `$returnstatus` (Maximum) zurueck.
- **Seiteneffekte:** `global $USER`; ruft pro Action `apply_action` (potenziell DB-Writes/Notifications je Action-Typ).
- **Aufrufkette:** Aus dem Buchungs-/Storno-Flow (booking_option/booking_bookit) gerufen; ruft `return_action` + `$action->apply_action`.
- **Bewertung:** C — Rueckgabe-Inkonsistenz: `$returnstatus` wird als Maximum gepflegt, aber `return $status` (actions_info.php:332) liefert nur den letzten Iterationswert; bei mehreren Actions kann der "abort"-Status (1) verloren gehen oder faelschlich durchschlagen. Echter latenter Bug.

### `return_action(stdClass $actiondata): ?booking_action` — private static
- **Zweck:** Instanziiert die Action-Klasse anhand `action_type`; faengt moodle_exception ab und liefert dann null.
- **Seiteneffekte:** keine direkt.
- **Aufrufkette:** Von `apply_actions`.
- **Bewertung:** C — catch faengt nur `moodle_exception`; ein nicht existierender Klassenname loest jedoch `Error` (nicht moodle_exception) aus und wird nicht abgefangen (actions_info.php:343-347) → Fatal statt null. Duplikat zu `get_action` (zwei Wege, dieselbe Klasse zu bauen).

## Zusammenfassung
Keine trivialen Akzessoren/Konstruktoren (rein statische Utility-Klasse). 11 Methoden, davon 4 mit Score C (Null-Deref-Risiken, Rueckgabe-Bug, Exception-Typ-Mismatch, leichte Duplizierung get_action/return_action). Kein kritisches SQL-Bauen, keine tiefe Schachtelung.
