# applybookingrules — Methoden-Doku
**Datei:** `classes/option/fields/applybookingrules.php` · **LOC:** 257 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`applybookingrules` ist eine `field_base`-Spezialisierung, die je Buchungsoption steuert, welche Booking-Rules uebersprungen werden (Opt-out/Opt-in-Modus). Die Daten werden im JSON der Option unter `skipbookingrules` (Rule-ID-Liste) und `skipbookingrulesmode` (0=opt-out, 1=opt-in) gehalten. Persistenz: JSON-Spalte der Option via `booking_option::add_data_to_json`/`remove_key_from_json`/`get_value_of_json_by_key`. Kollaborateure: `booking_rules` (gespeicherte Regeln je Kontext), `context`/`context_module`/`context_system`, `singleton_service` (Instance-Settings fuer Kontextnamen), `field_base::check_for_changes`. Zusaetzlich der statische Laufzeit-Helfer `apply_rule()`, den die Rules-Engine konsultiert.

## Methoden

### `public static prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Schreibt `skipbookingrules`/`skipbookingrulesmode` ins JSON von `$newoption` (oder entfernt beide Keys, wenn beide leer) — bewusst BEVOR das JSON gespeichert wird. Sammelt anschliessend Change-Diffs ueber Mock-Daten.
- **Parameter:** `$formdata` (per Ref), `$newoption` (per Ref), `$updateparam`/`$returnvalue` (ungenutzt). **Rueckgabe:** das nicht-leere der beiden Change-Arrays (`mode` bevorzugt, sonst `rules`).
- **Seiteneffekte:** Mutiert das JSON von `$newoption`; keine direkten DB-Writes. Instanziiert `new applybookingrules()` fuer den Change-Vergleich.
- **Aufrufkette:** Von der Field-Save-Pipeline gerufen; ruft `booking_option`-JSON-Helfer + geerbtes `check_for_changes`.
- **Bewertung:** **C** — (1) Im `else`-Zweig (mind. eines der Felder gesetzt) werden `$formdata->skipbookingrules` und `$formdata->skipbookingrulesmode` *ohne* `?? null` an `add_data_to_json` uebergeben — ist nur eines gesetzt, kann der Zugriff auf das andere eine Undefined-Property-Notice werfen (die Mock-Daten weiter unten nutzen korrekt `?? []`/`?? 0`, der JSON-Write-Pfad nicht). (2) Die Rueckgabe `empty($changes1) ? $changes2 : $changes1` verwirft stillschweigend Aenderungen am jeweils anderen Feld, wenn beide gleichzeitig geaendert wurden. (3) `// Todo: Write changes function`-Kommentar signalisiert unfertige Diff-Logik.

### `public static instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut den Modus-Select (opt-out/opt-in) und ein Autocomplete-Multiselect der im Kontext gespeicherten Regeln (Label `Regelname (Kontextname)`).
- **Parameter:** `$mform` (per Ref), `$formdata` (per Ref, liefert `context`/`cmid`), restliche ungenutzt. **Rueckgabe:** void.
- **Seiteneffekte:** Kontextaufloesung (`context_module`/`context_system`); `booking_rules::get_list_of_saved_rules_by_context($context->id)`; je Regel `json_decode` + `context::instance_by_id` + ggf. `singleton_service::get_instance_of_booking_settings_by_cmid` fuer den Anzeigenamen; `get_string`-Reads.
- **Aufrufkette:** Von der Option-Formular-Definition gerufen.
- **Bewertung:** **C** — N+1-Muster: pro Regel ein `context::instance_by_id` und (fuer Nicht-System-Kontexte) ein Booking-Settings-Lookup. Zudem ueberschreibt die Schleife die zuvor aufgeloeste Variable `$context` (Zeile 177) mit dem Regel-Kontext — bei vielen Regeln nur Lese-Last, aber das Shadowing macht die spaetere Kontext-Bedeutung uneindeutig.

### `public static set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Befuellt Modus und Regel-Liste fuer das Formular: beim Import (`importing` + Komma-String) per `explode`, sonst aus dem Options-JSON.
- **Parameter:** `$data` (per Ref), `$settings` (ungenutzt). **Rueckgabe:** void.
- **Seiteneffekte:** Im Nicht-Import-Pfad zwei `booking_option::get_value_of_json_by_key($data->id, ...)`-Reads.
- **Aufrufkette:** Von der Form-Befuellung gerufen.
- **Bewertung:** **B** — klar; minimaler Doppel-Read aufs selbe JSON.

### `public static apply_rule(int $optionid, int $ruleid): bool` — public static
- **Zweck:** Laufzeit-Entscheidung, ob eine konkrete Regel auf eine konkrete Option angewendet wird. Bei Modus 0 (opt-out): alle Regeln ausser den gelisteten; bei Modus 1 (opt-in): nur die gelisteten.
- **Parameter:** `$optionid`, `$ruleid`. **Rueckgabe:** `bool` (true = Regel anwenden).
- **Seiteneffekte:** Zwei `booking_option::get_value_of_json_by_key`-Reads je Aufruf.
- **Aufrufkette:** Von der Booking-Rules-Engine pro (Option, Regel)-Kombination konsultiert.
- **Bewertung:** **B** — korrekte Opt-in/Opt-out-Logik mit Array-Guard; potenziell hot-path mit JSON-Reads pro (Option,Regel) ohne Caching, hier aber gemildert durch `get_value_of_json_by_key`-Implementierung. Docblock fehlt der `@return bool`-Typ in der Signatur (nur PHPDoc).

### Triviale Properties
Statische Konfig-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`) sind reine Deklarationen.

## Bewertungs-Resümee
Funktional korrekte Skip-Rules-Verwaltung mit sauberer Opt-in/Opt-out-Semantik. Schwaechen: unguardierte `$formdata`-Zugriffe im JSON-Write-Pfad (Notice-Risiko bei Teil-Daten), Change-Diff verwirft eine von zwei gleichzeitigen Aenderungen, `$context`-Shadowing und N+1-Lookups im Formularaufbau. Keine Datenverlust- oder Sicherheitsprobleme. Klassen-Score **C / P3**.
