# availability — Methoden-Doku
**Datei:** `classes/option/fields/availability.php` · **LOC:** 295 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`availability` ist eine `field_base`-Spezialisierung, die die Verfuegbarkeits-Bedingungen einer Buchungsoption verwaltet — das JSON-Feld `availability` (eine Liste serialisierter `bo_condition`-Eintraege) plus den abgeleiteten `sqlfilter`. Sie ist POSTSAVE (`MOD_BOOKING_EXECUTION_POSTSAVE`) und delegiert den Grossteil an `bo_info` (Conditions-API: Formularaufbau, JSON-Persistenz, Defaults, Validierung). Persistenz: Spalten `availability` und `sqlfilter` der Option plus condition-spezifische Tabellen (via `bo_info::save_json_conditions_from_form`). Kollaborateure: `bo_info`, die Schwester-Felder `bookingopeningtime`/`bookingclosingtime` (werden mitausgefuehrt), `singleton_service` (Option-Settings), `$DB` (Import-Lookups course/cohort). Diese Klasse ueberschreibt `check_for_changes` selbst.

## Methoden

### `public static prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Persistiert die im Formular hinzugefuegten JSON-Conditions, fuehrt die abhaengigen Felder `bookingopeningtime`/`bookingclosingtime` mit aus und setzt `availability`/`sqlfilter` auf `$newoption`.
- **Parameter:** `$formdata` (per Ref), `$newoption` (per Ref), `$updateparam` (an die Schwester-Felder durchgereicht), `$returnvalue` (ungenutzt). **Rueckgabe:** Changes-Array aus `check_for_changes`.
- **Seiteneffekte:** `bo_info::save_json_conditions_from_form($formdata)` (kann condition-Tabellen schreiben); ruft `bookingopeningtime::prepare_save_field` und `bookingclosingtime::prepare_save_field`; mutiert `$newoption->availability`/`->sqlfilter`.
- **Aufrufkette:** Von der Field-Save-Pipeline gerufen; orchestriert die beiden Zeitfelder.
- **Bewertung:** **C** — (1) `$newoption->sqlfilter = $formdata->sqlfilter;` greift ohne `?? null` direkt auf `$formdata->sqlfilter` zu — fehlt der Schluessel, Undefined-Property-Notice (das `availability` daneben ist mit `?? '[]'` korrekt geguardet). (2) Das Mitausfuehren fremder Felder (`bookingopeningtime`/`bookingclosingtime`) ist eine versteckte Kopplung — der Kommentar erklaert sie, aber die Reihenfolge-Abhaengigkeit ist fragil.

### `public static instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt die Verfuegbarkeits-Bedingungen ueber `bo_info::add_conditions_to_mform($mform, $optionid)` ins Formular ein.
- **Parameter:** `$mform` (per Ref), `$formdata` (per Ref, liefert `id` als optionid), restliche ungenutzt. **Rueckgabe:** void.
- **Seiteneffekte:** delegiert komplett an `bo_info`.
- **Aufrufkette:** Von der Option-Formular-Definition gerufen.
- **Bewertung:** **B** — schlanke Delegation; offener `// Todo: expert/simple mode`-Kommentar signalisiert unfertige Modus-Unterstuetzung.

### `public static set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Befuellt das Formular aus dem `availability`-JSON: im Import-Pfad wird das JSON ggf. aus `$data` selbst genommen und die Convenience-Keys `boavenrolledincourse`/`boavenrolledincohorts` (Komma-Shortnames/Idnumbers) in echte Course-/Cohort-IDs + Restrict-Flags + Operator uebersetzt; im Normalpfad kommen die Werte aus `$settings` und die Zeitfelder werden mitgesetzt. Abschliessend werden Condition-Defaults aus dem JSON via `bo_info::set_defaults` gesetzt.
- **Parameter:** `$data` (per Ref), `$settings`. **Rueckgabe:** void.
- **Seiteneffekte:** `global $DB`; im Import-Pfad zwei `get_records_sql` (course-by-shortname, cohort-by-idnumber); ruft `bookingopeningtime::set_data`/`bookingclosingtime::set_data` (Normalpfad) und `bo_info::set_defaults`.
- **Aufrufkette:** Von der Form-Befuellung / vom Importer gerufen.
- **Bewertung:** **C** — lang (~73 LOC) mit doppeltem Import/Normal-Zweig; im Import-Zweig kann `$availability` unbelegt bleiben, wenn `$data->availability` gesetzt war (dann wird nur in den `else`-Ast `$data->availability` geschrieben, nicht in der `if`-Variante) — die spaetere `if (!empty($availability))`-Pruefung deckt das ab, aber der Kontrollfluss ist verschachtelt. Import-Lookups sind sinnvoll geparamtert (kein SQL-Injection).

### `public check_for_changes(stdClass $formdata, field_base $self, $mockdata = '', string|null $key = null, $value = ''): array` — public
- **Zweck:** Vergleicht die gespeicherte `availability` (aus Option-Settings) mit der im Formular eingegangenen und erzeugt einen Change-Eintrag fuer das `bookingoption_updated`-Event.
- **Parameter:** `$formdata`, `$self`, restliche ungenutzt. **Rueckgabe:** Changes-Array oder leer.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings($formdata->optionid ?? $formdata->id)`.
- **Aufrufkette:** Aus `prepare_save_field` gerufen.
- **Bewertung:** **C** — **Bug:** im `otherdata`-Block werden *sowohl* `oldavailability` *als auch* `newavailability` auf `$settings->availability` (den ALTEN Wert) gesetzt (Zeile 275/276) — der neue Wert `$formdata->availability` wird nie uebernommen. Die Change-Beschreibung des Verfuegbarkeits-Feldes meldet daher immer alt == neu, der tatsaechliche neue Zustand geht im Event-Log verloren. Zudem castet die Klasse `MOD_BOOKING_CLASSES_EXCLUDED_FROM_CHANGES_TRACKING`-Gate korrekt, aber die Methode dupliziert die geerbte `field_base`-Variante mit dieser fehlerhaften Auspraegung.

### `public static validation(array $data, array $files, array &$errors): array` — public static
- **Zweck:** Form-Validierung; delegiert vollstaendig an `bo_info::validation`.
- **Seiteneffekte:** keine eigenen. **Rueckgabe:** `$errors`. **Bewertung:** **A** (reine Delegation).

### Triviale Properties
Statische Konfig-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers` (12 Condition-Restrict-Keys), `$incompatiblefields` (Easy-Mode-Felder)) sind reine Deklarationen.

## Bewertungs-Resümee
Zentrales, stark an `bo_info` delegierendes Verfuegbarkeits-Feld mit umfangreichem Import-Mapping. Hauptproblem ist der Change-Tracking-Bug in `check_for_changes` (old==new, neuer Availability-Wert wird im Event-Log nicht festgehalten) — funktional fuer das Speichern unkritisch, aber die `bookingoption_updated`-Aenderungsbeschreibung ist falsch. Dazu der unguardierte `$formdata->sqlfilter`-Zugriff (Notice-Risiko) und versteckte Kopplung an die Zeitfelder. Klassen-Score **C / P2**.
