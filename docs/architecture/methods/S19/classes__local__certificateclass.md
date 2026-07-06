# certificateclass — Methoden-Doku
**Datei:** `classes/local/certificateclass.php` · **LOC:** 417 · **Subsystem:** S19 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S19_certificates.md)

## Klassenueberblick
Statische Helfer-Klasse (`mod_booking\local\certificateclass`) fuer die Ausstellung von Zertifikaten ueber das externe Plugin `tool_certificate`. Zentrale Methode `issue_certificate()` baut den Platzhalter-Datensatz (Buchungsoption, Custom Fields, Lehrer, Sessions, Dauer, Kompetenzen, Bedingung) zusammen, stellt das Zertifikat aus, erzeugt das PDF und triggert das Event `certificate_issued`. Daneben Voraussetzungs-Pruefungen (`required_options_fulfilled`, `one_required_option_fulfilled`) sowie diverse private Formatierungs-Helfer. Kollaborateure: `singleton_service`, `booking_option`/`booking_option_settings`, `booking_handler`, `placeholders\customfields`, `dates_handler`, `tool_certificate\template`/`certificate`, `core_competency\competency`, Event `certificate_issued`.

## Methoden

### `public static issue_certificate(int $optionid, int $userid, int $completeddate = 0, int $templateid = 0, ?int $expirydatetype = null, ?int $expirydateabsolute = null, ?int $expirydaterelative = null, ?stdClass $condition = null): int` — public
- **Zweck:** Stellt fuer einen User zu einer Buchungsoption ein Zertifikat aus (Template-Aufloesung, Ablaufdatum, Platzhalter-Daten, PDF, Event). Gibt die Issue-ID zurueck (0 wenn nicht ausgestellt).
- **Parameter:** Option/User-IDs, optional Abschlussdatum, Template-ID, Ablauf-Konfiguration (Typ/absolut/relativ), optionale Condition (`stdClass`).
- **Rueckgabe:** `int` Issue-ID bzw. `0` bei Abbruch/Deaktivierung.
- **Seiteneffekte:** DB-Read `tool_certificate_issues` (via `$DB->get_record`); Config-Read `get_config('booking','certificateon')`; mehrere JSON-Key-Reads ueber `booking_option::get_value_of_json_by_key`; externe Calls `template::instance/issue_certificate/create_issue_file`, `toolCertificate::calculate_expirydate` (→ DB-Write in `tool_certificate_issues` + PDF-Datei durch Fremd-Plugin); Globals via `singleton_service::set_temp_values_for_certificates`/`unset_temp_values_for_certificates`; Event `\mod_booking\event\certificate_issued` getriggert. Liest `global $DB`.
- **Aufrufkette:** Oeffentlicher Einstieg, gerufen aus Completion-/Issue-Logik des Plugins (z.B. bei Abschluss einer Option). Ruft alle privaten `return_*`-Helfer + `get_required_options_data`.
- **Bewertung:** D — 127 LOC (zu lang, certificateclass.php:58-184), mehrere Verantwortungen (Template-Aufloesung, Ablaufberechnung, Platzhalter-Assembly, PDF, Event) in einer Methode; statische God-Calls auf `singleton_service`/`booking_option`; potenzieller NULL-Deref: `$condition->id ?? 0` in Zeile 153 ist NULL-safe, aber die Befuellung von `$conditionfields` haengt von `!empty($condition)` ab — `$data`-Merge nutzt `$conditionfields ?? []`, sauber. Tempwerte-Set/Unset sind nicht exception-sicher (kein try/finally → bei Fehler in issue/create bleibt Temp-State haengen).

### `private static get_required_options_data(booking_option_settings $settings, int $userid): array` — private
- **Zweck:** Sammelt fuer das Event die Abschlussdaten der aktuellen Option plus aller konfigurierten Pflicht-Optionen (`certificaterequiresotheroptions`).
- **Parameter:** Option-Settings, User-ID. **Rueckgabe:** `array` (optionid → {optionid, optionname, completed}).
- **Seiteneffekte:** JSON-Key-Read; `singleton_service::get_instance_of_booking_option_settings`/`get_instance_of_booking_answers`; `is_activity_completed` (DB-Reads via booking_answers-Cache).
- **Aufrufkette:** Nur aus `issue_certificate`. Ruft singleton_service + booking_answers.
- **Bewertung:** B — fokussiert, ~35 LOC; leichte Duplikation der Pflichtoptionen-Schleife mit `required_options_fulfilled`/`one_required_option_fulfilled`.

### `private static return_competencies_for_certificate(string $competencies): string` — private
- **Zweck:** Wandelt CSV-Liste von Kompetenz-IDs in komma-separierte Shortnames.
- **Seiteneffekte:** `core_competency\competency::get_record` (DB-Read pro ID).
- **Aufrufkette:** Aus `issue_certificate`.
- **Bewertung:** C — N+1-DB-Read in Schleife (certificateclass.php:247-250); kein NULL-Guard: `competency::get_record` kann `false` liefern → `$competency->get(...)` wuerde fatalen Fehler werfen bei unbekannter ID.

### `private static return_teachers_for_certificate(array $teachers): string` — private
- **Zweck:** Formatiert Lehrer-Objekte zu `<br />`-getrennter Namensliste. Rein in-memory.
- **Bewertung:** A — trivial, klar.

### `private static return_duration_for_certificate(object $settings): string` — private
- **Zweck:** Berechnet Gesamtdauer (aus Sessions oder Kurszeiten) und gibt lokalisierten String zurueck.
- **Seiteneffekte:** `get_string`.
- **Aufrufkette:** Aus `issue_certificate`.
- **Bewertung:** B — ~22 LOC, mehrere Zweige aber klar; vertretbar.

### `private static return_sessions_for_certificate(array $sessions): string` — private
- **Zweck:** Formatiert Sessions zu `<br />`-getrennten Datumsbereichen.
- **Seiteneffekte:** `dates_handler::prettify_optiondates_start_end`, `current_language()`.
- **Bewertung:** A — kurz, klar.

### `private static return_timeawarded_for_certificate(booking_option_settings $settings, int $userid, int $completeddate): string` — private
- **Zweck:** Ermittelt das Vergabedatum (Fallback auf completeddate/timemodified/now aus booking_answers) und formatiert via `userdate`.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_answers` → `get_usersonlist`; `userdate`, `get_string`.
- **Bewertung:** B — sauber, kommentierter Fallback-Pfad.

### `public static required_options_fulfilled(booking_option_settings $settings, int $userid): bool` — public
- **Zweck:** Prueft, ob alle (bzw. je nach Modus mindestens eine) Pflicht-Optionen abgeschlossen sind, bevor ein Zertifikat ausgestellt werden darf.
- **Seiteneffekte:** 2x JSON-Key-Read (`certificaterequiresotheroptions`, `certificaterequiredoptionsmode`); `singleton_service`-Calls + `is_activity_completed`.
- **Aufrufkette:** Oeffentlich, aus der Issue-Vorpruefung. Delegiert bei Modus≠0 an `one_required_option_fulfilled`.
- **Bewertung:** B — klar; Schleife dupliziert Logik mit `get_required_options_data`/`one_required_option_fulfilled`.

### `public static one_required_option_fulfilled(array $requiredoptions, int $userid): bool` — public
- **Zweck:** Liefert true, sobald mindestens eine Pflicht-Option abgeschlossen ist.
- **Seiteneffekte:** `singleton_service`-Calls + `is_activity_completed` in Schleife.
- **Bewertung:** B — kurz; Schleifen-Duplikat zu `required_options_fulfilled`.
