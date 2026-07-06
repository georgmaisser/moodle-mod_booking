# aftersubmitaction — Methoden-Doku
**Datei:** `classes/option/fields/aftersubmitaction.php` · **LOC:** 182 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`aftersubmitaction` ist eine `field_base`-Spezialisierung, die kein persistentes Optionsfeld kapselt, sondern das Verhalten *nach* dem Absenden des Edit-Option-Formulars steuert (Zurueck zur returnurl / auf dem Formular bleiben / neue Option anlegen). Sie manipuliert dazu die `returnurl` von `$formdata` und `$newoption`. Persistenz: keine eigene Tabelle/JSON-Spalte — der Wert ist rein transient (Redirect-Steuerung). Kollaborateure: `moodle_url` (URL-Bau), `field_base` (Basis), Konstanten `MOD_BOOKING_OPTION_FIELD_AFTERSUBMITACTION` / `MOD_BOOKING_EXECUTION_NORMAL` / `MOD_BOOKING_HEADER_BOOKINGOPTIONTEXT`. Vollstaendig statisch.

## Methoden

### `public static prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Wertet `$formdata->aftersubmitaction` aus und setzt die `returnurl` auf `$formdata` und `$newoption` so um, dass nach dem Speichern der gewuenschte Redirect erfolgt (`submitandadd` → neues, leeres Edit-Formular; `submitandstay` → selbe Option erneut; `submitandgoback` → bestehende returnurl oder view.php).
- **Parameter:** `$formdata` (per Ref), `$newoption` (per Ref, Ziel), `$updateparam` (ungenutzt), `$returnvalue` (ungenutzt). **Rueckgabe:** immer leeres Array `[]`.
- **Seiteneffekte:** Keine DB-Writes; mutiert `returnurl` auf beiden uebergebenen Objekten via `moodle_url::out(false)`.
- **Aufrufkette:** Von der Field-Save-Pipeline (`fields_info`) je Save aufgerufen.
- **Bewertung:** **B** — Funktional ok, aber: (1) der Docblock deklariert `@return string`, tatsaechlich wird `array` zurueckgegeben (Signatur/Doc-Mismatch). (2) Im `submitandgoback`-Zweig bei leerer returnurl wird `view.php` mit `optionid => $newoption->id` gebaut, was bei einer *neuen* Option (id noch leer) eine optionid-lose URL liefert. (3) `submitandstay` bricht bei leerer `$newoption->id` ab, ohne Fallback — fuer neue Optionen wird die Aktion still ignoriert.

### `public static instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt das Select `aftersubmitaction` mit den drei Aktionen hinzu; bei neuer Option (`empty($formdata['id'])`) wird `submitandstay` entfernt, da keine optionid fuer die returnurl existiert.
- **Parameter:** `$mform` (per Ref), `$formdata` (per Ref), restliche ungenutzt. **Rueckgabe:** void.
- **Seiteneffekte:** `closeHeaderBefore` + `addElement` auf `$mform`; `get_string`-Reads.
- **Aufrufkette:** Von der Option-Formular-Definition (`fields_info`) gerufen.
- **Bewertung:** **A** — schlanker, klarer Formular-Aufbau.

### `public static set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Standard-Datentransfer in das Formular; hier bewusst No-op (Wert ist transient, kein gespeicherter Default).
- **Seiteneffekte:** keine. **Rueckgabe:** void (`return;`). **Bewertung:** **A** (trivialer No-op).

### Triviale Properties
Statische Konfig-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`) sind reine Deklarationen ohne Logik.

## Bewertungs-Resümee
Kleine, gut lesbare Steuer-Klasse fuer das Post-Submit-Verhalten ohne eigene Persistenz. Schwaechen rein kosmetisch/Randfall: Doc/Return-Mismatch (`string` vs. `array`), optionid-lose view.php-URL bei neuer Option im `submitandgoback`-Pfad. Keine sicherheits- oder datenkritischen Probleme. Klassen-Score **B / P3**.
