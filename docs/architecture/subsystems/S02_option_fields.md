# S02 — option_fields

## Zweck & Grenzen

Dieses Subsystem implementiert die **feld-basierte Form- und Persistenz-Architektur einer Buchungsoption**. Statt eines monolithischen `moodleform` mit hunderten Elementen wird das Optionsformular aus ~79 unabhängigen **Feld-Klassen** (`classes/option/fields/*.php`) zusammengesetzt, die alle den Vertrag `mod_booking\option\fields` (über die abstrakte Basis `field_base`) erfüllen. Jede Feld-Klasse kapselt einen logischen Aspekt einer Option (Titel, Preis, Termine, Verfügbarkeit, Lehrer, Zertifikat, Slotbooking, …) und kennt ihren kompletten Lebenszyklus: Formulardefinition, Validierung, Übernahme aus dem Formular (`prepare_save_field`/`save_data`), Rückbefüllung des Formulars (`set_data`) und Änderungs-Tracking (`check_for_changes`).

Der Orchestrator `fields_info` läuft über die für Kontext/Capability **konfigurierten** Felder (siehe `optionformconfig`) und ruft die jeweiligen Lifecycle-Methoden auf. Welche Felder pro Capability/Kontext sichtbar sind, regelt `optionformconfig_info` (Tabelle `booking_form_config`).

Zusätzlich gehören die **Terminverwaltung** (`optiondate`, `dates_handler`, `time_handler`, `type_resolver`), die **Customfields-Infrastruktur** (`booking_handler` für Optionen, `optiondate_cfields` für Termin-Customfields) sowie der Hilfsdienst `override_user_field` (Mocking von User-Profilfeldern für Verfügbarkeitsbedingungen) zum Subsystem.

**Grenzen:** Das eigentliche `moodleform`/`dynamic_form` (z. B. `option_form`, `editoptions`) liegt außerhalb; ebenso `booking_option::update()` (Persistenz-Einstieg, der `fields_info::prepare_save_fields`/`save_fields_post` aufruft) und `booking_option_settings` (das Lese-DTO, aus dem `set_data` liest). Diese zentralen Klassen werden referenziert, gehören aber zu anderen Subsystemen.

## Position im Gesamtsystem

```
option_form / editoptions (Form-Layer, S-extern)
        │  instance_form_definition / validation / definition_after_data
        ▼
   fields_info  ──get_field_classes──►  optionformconfig_info ──► {booking_form_config}
        │                                         ▲
        │  (pro Feld-Klasse)                       │ Capability+Kontext-Auflösung
        ▼
   fields/<feld>.php  (extends field_base implements fields)
        │  prepare_save_field / save_data / set_data / check_for_changes
        ▼
 booking_option::update()  ──persistiert──►  {booking_options}, {booking_optiondates}, ...
        ▲                                         │
        │  set_data liest aus                     ▼
 booking_option_settings (Lese-DTO)        optiondate / dates_handler / *_cfields
```

`booking_option::update()` (extern) ruft `fields_info::prepare_save_fields()` (Normal-Save-Felder, Spalten von `booking_options`) und danach `fields_info::save_fields_post()` (Postsave-Felder, brauchen die `optionid`) auf. Beim Formularaufbau ruft das Form `fields_info::instance_form_definition()`/`definition_after_data()`/`validation()`/`set_data()`.

## Schlüsselkonzepte

- **Feld-Vertrag (`fields` / `field_base`):** Jedes Feld ist eine Klasse mit ausschließlich **statischen** Lifecycle-Methoden und statischen Metadaten-Properties:
  - `$id` (int, Sortier-/Ausführungsreihenfolge, Konstanten `MOD_BOOKING_OPTION_FIELD_*`),
  - `$save` (`MOD_BOOKING_EXECUTION_NORMAL` vs. `…_POSTSAVE`),
  - `$header` (`MOD_BOOKING_HEADER_*`, unter welcher Formular-Sektion das Feld erscheint),
  - `$fieldcategories` (`STANDARD`/`NECESSARY`/`EASY` — steuert Default-Sichtbarkeit),
  - `$alternativeimportidentifiers` (alternative Schlüssel beim CSV-Import),
  - `$incompatiblefields` (gegenseitig ausschließende Felder).
- **Zwei-Phasen-Persistenz:** „Normal"-Felder schreiben Werte direkt in das `$newoption`-stdClass (= Spalten von `booking_options`); „Postsave"-Felder (Preis, Termine, Lehrer, Bilder, Customfields, Slotbooking …) brauchen die bereits vergebene `optionid` und werden danach via `save_data` in Nebentabellen geschrieben.
- **Kontext-/Capability-gesteuerte Feldmenge:** `optionformconfig_info` liefert je `contextid` + Capability (Expertenformular vs. 5 reduzierte Formulare) eine JSON-Liste aktiver Felder; Konfiguration wird in `booking_form_config` gespeichert, vererbt sich entlang des Kontextpfads (System → Coursecat → Course → Module).
- **Änderungs-Tracking:** `field_base::check_for_changes()` vergleicht alten (`set_data` aus Settings) und neuen Formularwert und liefert ein `changes`-Array; `get_changes_description()` macht Werte (User-IDs, Timestamps, Checkboxen) menschenlesbar für das `bookingoption_updated`-Event.
- **Optiontyp-Resolver:** `type_resolver` normalisiert `optiontype` (Default/Selflearningcourse/Slotbooking) und synchronisiert abhängige Flags (`selflearningcourse`, `slot_enabled`) bidirektional; Lizenz-Gate für Slotbooking.
- **Termine als eigenes Aggregat:** `optiondate` (CRUD eines einzelnen Termins inkl. Kalender-Event, Entities, Termin-Customfields), `dates_handler` (Datums-Serien-Parsing/Formatierung, Semester-Termine), `time_handler` (Zeit-Prettify).

## Datenfluss

**Formular anzeigen (set_data + Definition):**
1. Form ruft `fields_info::set_data($data)` → für jede konfigurierte Feld-Klasse `set_data($data, $settings)`, die den gespeicherten Wert aus `booking_option_settings` ins `$data`-Objekt überträgt.
2. `fields_info::instance_form_definition($mform, $formdata)` → pro Feld `instance_form_definition(...)` fügt mform-Elemente unter dem passenden Header hinzu (`add_header_to_mform`).
3. `fields_info::definition_after_data()` → pro Feld `definition_after_data()` + Wiederherstellung des Header-Collapse-Zustands (`restore_header_collapse_state`).

**Speichern:**
1. `booking_option::update()` ruft `fields_info::prepare_save_fields($formdata, $newoption)`:
   - `type_resolver::normalize_formdata()` setzt `optiontype`/Flags,
   - für jede Normal-Feld-Klasse `prepare_save_field()` → schreibt Spaltenwerte in `$newoption`.
2. Option-Datensatz wird in `booking_options` geschrieben (extern), `optionid` steht fest.
3. `fields_info::save_fields_post($formdata, $option)` → für jede Postsave-Klasse `save_data()` → Preis, Termine (`optiondate::save`/`dates_handler::save_from_form`), Lehrer, Bilder, Customfields (`booking_handler::instance_form_save`), Entities etc.
4. `fields_info::all_changes_collected_actions()` → Hook `changes_collected_action()` pro Feld (z. B. Kind-Optionen bei `recurringoptions`).

**Import (CSV):** `fields_info::ignore_class()` filtert Felder, die im Import nicht vorkommen (außer `NECESSARY`/Customfields/Preiskategorie-Spalten/`$alternativeimportidentifiers`).

## Dateien & Klassen

Legende Vorab-Score: A(best)…E(schlecht). Prio: P0…P3 / `-` (kein Handlungsbedarf).

### Kern (classes/option, classes/settings/optionformconfig, classes/customfield, classes/local)

| Datei | Klasse | Rolle | LOC | Methoden | Score | Prio |
|---|---|---|---|---|---|---|
| option/fields.php | `fields` | Interface (Feld-Vertrag) | 67 | 2 | A | - |
| option/field_base.php | `field_base` | Abstrakte Basis aller Felder | 432 | 13 | B | P2 |
| option/fields_info.php | `fields_info` | Orchestrator/Dispatcher über Feld-Klassen | 529 | 12 | C | P2 |
| option/type_resolver.php | `type_resolver` | Optiontyp normalisieren/auflösen | 108 | 4 | A | - |
| option/time_handler.php | `time_handler` | Zeit-Prettify/Intervall | 65 | 2 | A | - |
| option/optiondate.php | `optiondate` | Domänenobjekt + CRUD eines Termins | 379 | 5 | C | P3 |
| option/dates_handler.php | `dates_handler` | Datumsserien/Formatierung/Semester | 938 | 19 | D | P1 |
| settings/optionformconfig/optionformconfig_info.php | `optionformconfig_info` | Feldkonfiguration je Kontext/Capability | 411 | 9 | C | P2 |
| customfield/booking_handler.php | `booking_handler` | core_customfield-Handler für Optionen | 536 | 18 | C | P2 |
| customfield/optiondate_cfields.php | `optiondate_cfields` | Termin-Customfields (eigene Tabelle) | 333 | 7 | C | P3 |
| local/override_user_field.php | `override_user_field` | Mock von User-Profilfeldern (Cond.) | 212 | 5 | C | P3 |

### Feld-Klassen (classes/option/fields/) — alle `extends field_base implements fields`

Score-Konvention: triviale 1-Wert-Felder = **B/C, Prio `-`** (folgen exakt dem `field_base`-Muster). Größere/komplexere Felder einzeln bewertet.

| Datei | Klasse | $id-Konstante / Header | Save | LOC | Methoden | Score | Prio |
|---|---|---|---|---|---|---|---|
| actions.php | `actions` | ACTIONS / ACTIONS | post | 155 | 3 | B | - |
| addastemplate.php | `addastemplate` | TEMPLATESAVE | normal | 210 | 3 | B | P3 |
| address.php | `address` | ADDRESS / GENERAL | normal | 140 | 2 | B | - |
| addtocalendar.php | `addtocalendar` | ADDTOCALENDAR / DATES | post | 195 | 3 | B | P3 |
| addtogroup.php | `addtogroup` | ADDTOGROUP / GENERAL | post | 146 | 3 | B | - |
| aftercompletedtext.php | `aftercompletedtext` | BOOKINGOPTIONTEXT | normal | 168 | 3 | B | - |
| aftersubmitaction.php | `aftersubmitaction` | BOOKINGOPTIONTEXT | normal | 182 | 3 | B | - |
| annotation.php | `annotation` | ANNOTATION / GENERAL | normal | 168 | 3 | B | - |
| applybookingrules.php | `applybookingrules` | APPLYBOOKINGRULE / RULES | normal | 257 | 5 | C | P3 |
| attachment.php | `attachment` | ATTACHMENT / ADVANCED | post | 245 | 4 | C | P3 |
| availability.php | `availability` | AVAILABILITY | post | 295 | 4 | C | P2 |
| beforebookedtext.php | `beforebookedtext` | BOOKINGOPTIONTEXT | normal | 168 | 3 | B | - |
| beforecompletedtext.php | `beforecompletedtext` | BOOKINGOPTIONTEXT | normal | 168 | 3 | B | - |
| bookingclosingtime.php | `bookingclosingtime` | BOOKINGCLOSINGTIME / AVAIL. | normal | 245 | 4 | C | P3 |
| bookingopeningtime.php | `bookingopeningtime` | BOOKINGOPENINGTIME / AVAIL. | normal | 244 | 4 | C | P3 |
| bookingoptionimage.php | `bookingoptionimage` | OPTIONIMAGES / GENERAL | post | 260 | 4 | C | P3 |
| bookusers.php | `bookusers` | BOOKUSERS / GENERAL | post | 216 | 4 | C | P3 |
| canceluntil.php | `canceluntil` | CANCELUNTIL / ADVANCED | normal | 243 | 3 | C | P3 |
| certificate.php | `certificate` | CERTIFICATE | normal | 389 | 5 | C | P2 |
| competencies.php | `competencies` | COMPETENCIES | normal | 474 | 11 | D | P2 |
| courseendtime.php | `courseendtime` | COURSEENDTIME / GENERAL | normal | 141 | 4 | B | - |
| courseid.php | `courseid` | COURSEID / COURSES | normal | 423 | 6 | D | P2 |
| coursestarttime.php | `coursestarttime` | COURSESTARTTIME / DATES | normal | 203 | 4 | C | P3 |
| credits.php | `credits` | CREDITS / PRICE | normal | 162 | 3 | B | - |
| customfields.php | `customfields` | COSTUMFIELDS | post | 277 | 6 | C | P2 |
| description.php | `description` | DESCRIPTION / GENERAL | normal | 191 | 4 | B | - |
| disablebookingusers.php | `disablebookingusers` | DISABLEBOOKINGUSERS / ADV. | normal | 126 | 2 | B | - |
| disablecancel.php | `disablecancel` | DISABLECANCEL / ADVANCED | normal | 151 | 3 | B | - |
| duplication.php | `duplication` | DUPLICATION / GENERAL (NECESSARY) | normal | 174 | 3 | B | - |
| duration.php | `duration` | DURATION / COURSES | normal | 268 | 4 | C | P3 |
| easy_availability_previouslybooked.php | `easy_availability_previouslybooked` | EASY_AVAIL_PREVBOOKED | normal | 271 | 4 | C | P3 |
| easy_availability_selectusers.php | `easy_availability_selectusers` | EASY_AVAIL_SELECTUSERS | normal | 262 | 4 | C | P3 |
| easy_bookingclosingtime.php | `easy_bookingclosingtime` | EASY_BOOKINGCLOSINGTIME | normal | 186 | 4 | C | P3 |
| easy_bookingopeningtime.php | `easy_bookingopeningtime` | EASY_BOOKINGOPENINGTIME | normal | 188 | 4 | C | P3 |
| easy_text.php | `easy_text` | (Easy-Mode Titel) | normal | 149 | 4 | B | - |
| elective.php | `elective` | ELECTIVE | normal | 184 | 4 | C | P3 |
| enrolmentstatus.php | `enrolmentstatus` | ENROLMENTSTATUS | normal | 223 | 4 | C | P3 |
| entities.php | `entities` | ENTITIES | normal/post | 389 | 7 | D | P2 |
| eventslist.php | `eventslist` | EVENTSLIST | normal | 131 | 3 | B | - |
| formconfig.php | `formconfig` | FORMCONFIG | normal | 172 | 3 | B | P3 |
| groupid.php | `groupid` | GROUPID | normal | 216 | 3 | C | P3 |
| howmanyusers.php | `howmanyusers` | HOWMANYUSERS | normal | 128 | 2 | B | - |
| id.php | `id` | ID (NECESSARY) | normal | 173 | 3 | B | - |
| identifier.php | `identifier` | IDENTIFIER | normal | 157 | 3 | B | - |
| institution.php | `institution` | INSTITUTION | normal | 159 | 2 | B | - |
| invisible.php | `invisible` | INVISIBLE | normal | 205 | 3 | C | P3 |
| json.php | `json` | JSON | normal | 142 | 3 | B | - |
| location.php | `location` | LOCATION | normal | 153 | 2 | B | - |
| maxanswers.php | `maxanswers` | MAXANSWERS | normal | 155 | 3 | B | - |
| maxoverbooking.php | `maxoverbooking` | MAXOVERBOOKING | normal | 134 | 2 | B | - |
| minanswers.php | `minanswers` | MINANSWERS | normal | 127 | 2 | B | - |
| moveoption.php | `moveoption` | MOVEOPTION | normal/post | 280 | 4 | C | P3 |
| multiplebookings.php | `multiplebookings` | MULTIPLEBOOKINGS | normal | 240 | 4 | C | P3 |
| notificationtext.php | `notificationtext` | NOTIFICATIONTEXT | normal | 164 | 3 | B | - |
| optiondates.php | `optiondates` | OPTIONDATES / DATES | post | 358 | 7 | D | P2 |
| optiontype.php | `optiontype` | OPTIONTYPE | normal | 295 | 4 | C | P2 |
| pollurl.php | `pollurl` | POLLURL | normal | 239 | 4 | C | P3 |
| prepare_import.php | `prepare_import` | (Import-Vorbereitung) | normal | 171 | 3 | C | P3 |
| price.php | `price` | PRICE | post | 277 | 5 | C | P2 |
| priceformulaadd.php | `priceformulaadd` | (Preisformel +) | normal | 117 | 2 | B | - |
| priceformulamultiply.php | `priceformulamultiply` | (Preisformel ×) | normal | 117 | 2 | B | - |
| priceformulaoff.php | `priceformulaoff` | (Preisformel aus) | normal | 117 | 2 | B | - |
| recurringoptions.php | `recurringoptions` | RECURRINGOPTIONS | post | 869 | 12 | D | P1 |
| removeafterminutes.php | `removeafterminutes` | REMOVEAFTERMINUTES | normal | 132 | 2 | B | - |
| responsiblecontact.php | `responsiblecontact` | RESPONSIBLECONTACT | normal | 247 | 4 | C | P3 |
| returnurl.php | `returnurl` | RETURNURL | normal | 132 | 2 | B | - |
| sharedplaces.php | `sharedplaces` | SHAREDPLACES | normal | 445 | 8 | D | P2 |
| shoppingcart.php | `shoppingcart` | SHOPPINGCART | post | 327 | 6 | C | P2 |
| slotbooking.php | `slotbooking` | (id=206) / GENERAL | post | 801 | 8 | D | P1 |
| subbookings.php | `subbookings` | SUBBOOKINGS | normal | 119 | 2 | B | - |
| teachers.php | `teachers` | TEACHERS | post | 239 | 5 | C | P2 |
| template.php | `template` | TEMPLATE | normal | 309 | 4 | C | P3 |
| text.php | `text` | TEXT (Titel) | normal | 193 | 4 | B | P3 |
| timecreated.php | `timecreated` | TIMECREATED | normal | 162 | 2 | B | - |
| timemodified.php | `timemodified` | TIMEMODIFIED | normal | 146 | 2 | B | - |
| titleprefix.php | `titleprefix` | TITLEPREFIX | normal | 134 | 2 | B | - |
| usercreated.php | `usercreated` | USERCREATED | normal | 110 | 1 | B | - |
| usermodified.php | `usermodified` | USERMODIFIED | normal | 102 | 1 | B | - |
| waitforconfirmation.php | `waitforconfirmation` | WAITFORCONFIRMATION | normal | 199 | 3 | C | P3 |

---

## Methoden-Inventar (nicht-triviale Klassen)

### `fields` (Interface) — option/fields.php
- `prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue=null): array` — static; Formularwert in `$newoption` übernehmen.
- `instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig)` — static; Form-Elemente hinzufügen.

### `field_base` (abstrakt) — option/field_base.php
Gemeinsame Default-Implementierung + statische Metadaten (`$id`, `$save`, `$header`, `$incompatiblefields`).
- `prepare_save_field(...)` — static; Default: kopiert `$formdata->{key}` → `$newoption->{key}` (oder `$returnvalue`).
- `instance_form_definition(...)` — static; Default leer (override pro Feld).
- `validation(array $data, array $files, array &$errors): array` — static; Default no-op.
- `save_data(stdClass &$formdata, stdClass &$option)` — static; Default leer (Postsave-Hook).
- `set_data(stdClass &$data, booking_option_settings $settings)` — static; Default: liest `$settings->{key}` → `$data->{key}`.
- `definition_after_data(MoodleQuickForm &$mform, $formdata)` — static; Default leer.
- `return_classname_name()` / `return_full_classname(): string` — static; Klassennamen (kurz/voll).
- `get_subfields(): array` — static; Default `[]` (überschrieben von `customfields`).
- `changes_collected_action(array $changes, object $data, object $newoption, object $originaloption)` — static; Hook nach Sammeln aller Changes.
- `check_for_changes(stdClass $formdata, field_base $self, $mockdata='', ?string $key=null, $value=''): array` — **instanz**; vergleicht alt/neu (Sonderfälle text-Arrays/Objekte), ~85 LOC, viele Verzweigungen (field_base.php:234).
- `get_changes_description(array $changes): array` — **instanz**; Werte menschenlesbar (User-IDs/Timestamps/Checkboxen) für Event (field_base.php:329).
- `resolve_userid_as_readable_personparams(int $userid, string &$returnvalue): bool` — privat; User-Info anhängen.

### `fields_info` — option/fields_info.php (Orchestrator)
- `prepare_save_fields(stdClass &$formdata, stdClass &$newoption, int $updateparam): array` — static; ruft je Normal-Feld `prepare_save_field`, sammelt Feedback; Fehler werden in `$error[]` gesammelt aber **nicht ausgewertet** (fields_info.php:67).
- `get_class_name($classname)` / `get_namespace_from_class_name($classname): string` — static; Namensumwandlung; letzteres mit Sonderfällen `dates`→`optiondates`, `enrolementstatus`→`enrolmentstatus` (Tippfehler-Mapping, fields_info.php:119).
- `add_header_to_mform(MoodleQuickForm &$mform, string $headeridentifier)` — static; Header-Element + Icon (großes switch).
- `instance_form_definition(MoodleQuickForm &$mform, array &$formdata): array` — static; pro Feld Form-Definition.
- `validation(array $data, array $files, array &$errors)` — static; pro Feld Validierung.
- `save_fields_post(stdClass &$formdata, stdClass &$option, int $updateparam): array` — static; Postsave-Felder.
- `set_data(stdClass &$data): string` — static; pro Feld `set_data`, Fehlermeldung als Loop-Exit.
- `definition_after_data(...)` + `restore_header_collapse_state(...)` — static; Header-Collapse-State per stabilem Namensprefix wiederherstellen (Workaround für `data-random-ids`, fields_info.php:354).
- `get_available_field_class_ids(int $contextid, int $save=-1): array` — static; Hilfs-API.
- `get_field_classes(int $contextid, int $save=-1): array` — **privat**; zentrale Auflösung über `optionformconfig_info` (Capability+Config-JSON); Filter nach `$save` & `necessary/checked`.
- `ignore_class($data, $classname): bool` — privat; Import-Filter (Preiskategorie-/Alt-Identifier-Sonderfälle).
- `all_changes_collected_actions(...)` — static; Hook-Fan-out (Bug: liest `$formdata['optiondateid']` statt `$data`, fields_info.php:511).

### `type_resolver` — option/type_resolver.php
- `apply_license_rules(int $type): int` — privat; Slotbooking-Lizenz-Gate.
- `is_supported_type(int $type): bool` — privat.
- `resolve_type(stdClass $formdata, ?int $fallbacktype=null): int` — static; Typ aus Formular/Fallback.
- `normalize_formdata(stdClass &$formdata, ?int $fallbacktype=null): int` — static; setzt `optiontype`+abhängige Flags. Saubere, fokussierte Klasse.

### `time_handler` — option/time_handler.php
- `set_timeintervall(): array` — static; `['step'=>5]` falls Config gesetzt.
- `prettytime(int $timestamp, bool $nextfullhour=true): int` — static; auf volle Stunde runden.

### `optiondate` — option/optiondate.php (Domänenobjekt + CRUD)
Public Properties: `$id, $bookingid, $optionid, $eventid, $coursestarttime, $courseendtime, $daystonotify, $sent, $reason, $reviewed`; static `$instances`.
- `__construct(...)` — 10 Positions-Parameter.
- `getoptiondates(int $optionid)` — static; alle Termine als Objekte (`new self(...$record)`).
- `save(int $id=0, …, array $customfields=[]): optiondate` — static; Insert/Update mit Diff-Vergleich, Kalender-Event `bookingoptiondate_created`, User-Calendar-Einträge, Entities-Relation, Termin-Customfields (~120 LOC, optiondate.php:155).
- `compare_optiondates(array $old, array $new, int $mode=0): bool` — static; Diff Termin/Entities/Customfields je Modus.
- `delete($optiondateid)` — static; löscht Event, User-Events, Lehrer-Zuordnung, Entities, Customfields, Termin.

Kollaborateure: `singleton_service`, `teachers_handler`, `calendar`, `entitiesrelation_handler`, `optiondate_cfields`, Event `bookingoptiondate_created`.

### `dates_handler` — option/dates_handler.php (gemischte Verantwortung, 938 LOC)
Mischt Form-Aufbau, String-Parsing, Lokalisierung, Persistenz und Slot-Erzeugung — **klarer Kandidat zur Aufteilung**.
- `__construct(int $optionid=0, int $bookingid=0)`.
- `add_optiondates_for_semesters_to_mform(MoodleQuickForm &$mform, bool $loadexistingdates)` — Semester-/Serien-Form-Elemente + Renderer.
- `delete_all_option_dates(): void` — alle Termine löschen.
- `save_from_form(stdClass $fromform)` — `stillexistingdates`/`newoptiondates` diffen und via `optiondate::save/delete` persistieren.
- `get_optiondate_series(int $semesterid, string $reoccurringdatestring): array` — static; Terminserie über Semester (Feiertags-Filter).
- `is_on_a_holiday(stdClass $dateobj): bool` — privat static.
- `split_and_trim_reoccurringdatestring(...)`, `render_dayofweektime_strings(...)`, `prepare_day_info(...)`, `reoccurring_datestring_is_correct(...)` — static; Datums-String-Parsing (DE/EN Wochentage).
- `get_existing_optiondates(int $optionid): array`, `return_array_of_sessions_simple/…_datestrings(int $optionid)` — static; Anzeige-Helfer.
- `prettify_optiondates_start_end(...)`, `prettify_datetime(...)`, `get_localized_weekdays(...)`, `calculate_and_render_educational_units(...)` — static; Formatierung (request-lokaler Cache in `prettify_datetime`).
- `change_semester($cmid, $semesterid)` — static; alle Optionen neu terminieren + Cache-Purges.
- `add_values_from_post_to_form(object &$fromform)` — static; liest direkt aus `$_POST` (Hack-Kommentar im Code, dates_handler.php:710).
- `return_dates_with_strings(booking_option_settings $settings, …): array`, `create_slots($starttime, $endtime, $duration)` — static.

### `optionformconfig_info` — settings/optionformconfig/optionformconfig_info.php
Konstanten `NOCONFIGURATION/SHOWFIELD/HIDEFIELD`, `CAPABILITIES` (Expert + 5 reduzierte), static Cache `$arrayoffieldsets`.
- `destroy_singletons(): void` — Cache-Reset.
- `return_configured_fields(int $contextid=0): array` — static; alle Capabilities.
- `save_configured_fields(int $contextid, string $capability, string $json): string` — static; insert/update/delete in `booking_form_config`.
- `return_capability_for_user(int $contextid, int $userid=0): string` — static; erste passende Capability.
- `return_configured_fields_for_capability(int $contextid, string $capability): array` — static; **Kern**: alle Feldklassen via `core_component::get_component_classes_in_namespace` (+ bookingextension), Merge mit gespeichertem Record, Property-Backfill (~90 LOC, optionformconfig_info.php:196).
- `get_classname(string $context): string` — static; Lang-String-Auflösung.
- `get_unchecked_customfields(int $contextid, int $userid=0)` — static; unsichtbare Customfields.
- `return_message_stored_optionformconfig(int $contextid): string` — static; Kontextlevel-abhängige Meldung.
- `return_capabilities_from_db(int $contextid, string $capability)` — privat static; vererbungsfähige Suche entlang `context->path` (ORDER BY contextlevel DESC).

### `booking_handler` — customfield/booking_handler.php (extends core_customfield\handler)
Singleton; Konstanten Sichtbarkeit `MOD_BOOKING_VISIBLETOALL/…TOTEACHERS/NOTVISIBLE`.
- `create(int $itemid=0): handler` / `reset_caches()` — static; Singleton (Reset nur in Tests).
- `get_customfields(array $selectedshortnames=[]): array` — static; gecachte CF-Abfrage (MUC `customfields`).
- `field_save($instanceid, $shortname, $value)` — Einzelfeld speichern.
- `can_configure()/can_edit()/can_view()/uses_categories()` — Capability-Logik.
- `instance_form_definition(...)` — CF-Elemente + Kategorie-Header, respektiert `get_unchecked_customfields`.
- `set_parent_context()/get_parent_context()/get_configuration_context()/get_instance_context()/get_configuration_url()` — Kontext-Methoden (alle Instanz-Kontexte = **System** → Smell: keine Pro-Option-Kontexttrennung, booking_handler.php:356).
- `config_form_definition()`, `restore_instance_data_from_backup()` — CF-Konfig/Backup.
- `instance_form_validation(array $data, array $files=[])` — nur sichtbare Felder validieren.
- `instance_form_before_set_data_on_import(stdClass $instance)` — Import-Vorbelegung.
- `instance_form_save(stdClass $instance, bool $isnewinstance=false)` — Speichern (Multiselect-Fix, throwable geschluckt, booking_handler.php:498).
- `check_for_forbidden_shortnames_and_return_warning(): string` — Kollision CF-Shortname vs. Option-Property.

### `optiondate_cfields` — customfield/optiondate_cfields.php (Termin-Customfields, eigene Tabelle)
- `instance_form_definition(&$mform, array &$elements, $counter=1, $index=0, array $customfields=[])` — static; dynamische Form-Elemente bis `MOD_BOOKING_MAX_CUSTOM_FIELDS`.
- `get_list_of_submitted_cfields(array $formdata, $index): array` — static; CF aus Form lesen.
- `save_fields(int $optionid, int $optiondateid, array $customfields)` — static; insert/update/delete in `booking_customfields`.
- `return_customfields_for_optiondate(int $optiondateid): array` — static.
- `set_data(stdClass &$defaultvalues, int $optiondateid, int $idx)` — static; Form-Vorbelegung.
- `delete_cfields_for_optiondate($optiondateid)` — static.
- `compare_items(array $olditem, array $newitem): bool` — static; Diff für `optiondate::compare_optiondates`.

### `override_user_field` — local/override_user_field.php
- `__construct(int $cmid)`.
- `set_userprefs(string $param, int $userid=0): bool` — Param `feld_wert` als User-Preference `wert:::cmid` setzen (Standard- + Custom-Profilfelder).
- `password_is_valid(string $pwd=''): bool` — gegen `circumventcond.cvpwd` der Booking-Instanz.
- `get_value_for_user(string $profilefield, int $userid): string` — Preference cmid-scoped lesen.
- `get_circumvent_link(int $optionid): string` — URL zu `optionview.php` für Profilfeld-Verfügbarkeitsbedingungen (`userprofilefield_1_default`/`_2_custom`, Operatoren `=`,`~`,`[~]`).

### Größere Feld-Klassen (Auswahl)

- **`recurringoptions`** (option/fields/recurringoptions.php, 869 LOC, **D/P1**): Mutter-/Kind-Optionen. Neben dem Standard-Vertrag: `update_options(...)`, `update_records(...)` (privat), `apply_delta_to_field(...)` (privat), `update_recurring_date_sessions(...)` (privat), `allchildrenaction(...)` (privat), `unlink_child(...)` (privat), `changes_collected_action(...)`. Propagiert Änderungen auf Kind-Optionen — höchste Komplexität & Kopplung im Subsystem.
- **`slotbooking`** (option/fields/slotbooking.php, 801 LOC, **D/P1**): Slot-Konfiguration als JSON in `booking_options.json` (`booking_option::add_data_to_json`). `set_data` ~200 LOC; private Helfer `apply_semester_slot_defaults`, `extract_days_of_week`, `extract_teacher_pool_from_formdata`. Nutzt `type_resolver`, `semester`.
- **`sharedplaces`** (445 LOC, **D/P2**): Platz-Teilung zwischen Optionen; eigene `check_for_changes`, `get_sharedplaces_options`, `return_shared_places_where_sql`, `sync_sharedplaces_options` (SQL/Sync-Logik im Feld).
- **`competencies`** (474 LOC, **D/P2**): core_competency-Anbindung; `assign_competencies`, `get_competencies_including_framework` (privat), Filter-/Similar-Options-Helfer.
- **`courseid`** (423 LOC, **D/P2**): verknüpfter Moodle-Kurs; private `copy_moodle_course`/`create_copy` (Kurs-Duplizierung im Feld).
- **`entities`** (389, **D/P2**), **`certificate`** (389, **C/P2**), **`optiondates`** (358, **D/P2**), **`shoppingcart`** (327, **C/P2**), **`template`** (309, **C/P3**), **`optiontype`** (295, **C/P2**), **`availability`** (295, **C/P2**), **`price`** (277, **C/P2**), **`customfields`** (277, **C/P2**), **`teachers`** (239, **C/P2**): jeweils Standard-Vertrag + 1–3 Spezialmethoden (`save_data`, eigene `validation`, `check_for_changes`, `get_changes_description`, Sync-/Order-Helfer).

Alle übrigen Feld-Klassen implementieren ausschließlich `prepare_save_field`/`instance_form_definition` (+ optional `validation`/`set_data`) gemäß `field_base`-Muster und sind als triviale Ein-Wert-Felder zu lesen.

## Persistenz

| Tabelle | Geschrieben von | Inhalt |
|---|---|---|
| `booking_options` | Normal-Felder via `prepare_save_field` → `booking_option::update` (extern) | Spaltenwerte (Titel, Zeiten, Maxanswers, JSON …) |
| `booking_options.json` | `slotbooking`, `json`, diverse via `booking_option::add_data_to_json` | strukturierte Zusatzdaten (z. B. `slot_enabled`) |
| `booking_optiondates` | `optiondate::save/delete`, `dates_handler` | einzelne Termine |
| `booking_customfields` | `optiondate_cfields` | Termin-Customfields/Kommentare |
| `customfield_field` / `customfield_data` / `customfield_category` | `booking_handler` (core_customfield) | Options-Customfields |
| `booking_form_config` | `optionformconfig_info::save_configured_fields` | je Kontext+Capability die JSON-Feldliste |
| `booking_userevents`, `event` | `optiondate` | Kalender-/User-Events |
| `booking_holidays`, `booking_pricecategories` | gelesen (Feiertags-Filter, Import) | — |
| User-Preferences | `override_user_field` | gemocktes Profilfeld `wert:::cmid` |

**Caches:** MUC `mod_booking/customfields` (`booking_handler::get_customfields`), static request-Cache `optionformconfig_info::$arrayoffieldsets`, static `dates_handler::$prettytimestamps` + request-lokaler Cache in `prettify_datetime`, `cache_helper::purge/invalidate` in `dates_handler::change_semester` (`setbacksemesters`, `setbackoptionsettings`, `setbackoptionstable`, `setbackbookinginstances`).

## Extension-Points

- **Neues Optionsfeld:** Klasse in `mod_booking\option\fields\` (oder `bookingextension`-Plugin im Namespace `option\fields`) anlegen, `field_base` erweitern, eindeutiges `$id` (Konstante `MOD_BOOKING_OPTION_FIELD_*`) vergeben. `optionformconfig_info::return_configured_fields_for_capability` entdeckt sie automatisch via `core_component::get_component_classes_in_namespace`.
- **Interface `fields`** — minimaler Vertrag (prepare_save_field + instance_form_definition).
- **`$alternativeimportidentifiers` / `$incompatiblefields`** — Steuerung Import & gegenseitiger Ausschluss.
- **Hooks:** `changes_collected_action()` (nach Sammeln aller Changes), `definition_after_data()`, `get_subfields()` (Customfields).
- **Customfields:** `booking_handler` (Options-CF) als core_customfield-Handler; `optiondate_cfields` (Termin-CF) als eigene Mini-Infrastruktur.
- **bookingextension-Felder** werden in `optionformconfig_info` explizit mit eingesammelt.

## Bekannte Schulden (→ Blueprint)

1. **`dates_handler` (938 LOC, P1):** vermischt Form-Aufbau, `$_POST`-Lesen (`add_values_from_post_to_form`, dates_handler.php:710), String-Parsing, Lokalisierung, Persistenz, Cache-Purges und Slot-Erzeugung. Aufteilen in Parser/Formatter/Persistenz.
2. **`recurringoptions` (869 LOC, P1) & `slotbooking` (801 LOC, P1):** überladene Feld-Klassen mit Domänenlogik (Kind-Propagation bzw. Slot-Defaults), die eher in dedizierte Services gehörte; `slotbooking::set_data` ~200 LOC.
3. **`fields_info` Fehlerbehandlung:** `prepare_save_fields` sammelt Exceptions in `$error[]`, wertet sie aber nie aus (`// Todo: implement error handling.`, fields_info.php:67). `all_changes_collected_actions` greift auf undefiniertes `$formdata['optiondateid']` zu (fields_info.php:511) — toter/fehlerhafter Zweig.
4. **`field_base::check_for_changes` (~85 LOC, P2):** stark verzweigte Sonderfall-Behandlung für text-Arrays/Objekte; schwer testbar. `get_changes_description` enthält hartcodierte Feldnamen-Listen (User-/Timestamp-/Checkbox-Felder, field_base.php:343).
5. **`fields_info::get_namespace_from_class_name`:** Tippfehler-Mapping `enrolementstatus`→`enrolmentstatus` und `dates`→`optiondates` zementiert Altlasten (fields_info.php:119).
6. **`booking_handler` Kontextmodell (P2):** sämtliche `get_*context()` liefern `context_system` — keine echte Pro-Option/Pro-Kurs-Kontexttrennung; `instance_form_save` schluckt `Throwable` kommentarlos (booking_handler.php:498).
7. **God-Statics / Singleton-Service-Kopplung:** alle Felder rufen `singleton_service::*`, `context_module::instance`, globale Konstanten — flächige statische Kopplung erschwert Unit-Tests; keine sichtbaren Unit-Tests für Einzelfelder.
8. **Score-Inflation durch Boilerplate:** ~50 nahezu identische triviale Feld-Klassen (Copy-Paste von Header-Doc + Metadaten-Block ~80 Zeilen). Ein Generator/Trait könnte die Wiederholung reduzieren (P3).
