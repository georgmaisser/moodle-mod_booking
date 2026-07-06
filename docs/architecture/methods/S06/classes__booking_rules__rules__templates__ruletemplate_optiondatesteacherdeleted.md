# ruletemplate_optiondatesteacherdeleted — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_optiondatesteacherdeleted.php` · **LOC:** 88 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_optiondatesteacherdeleted` ist eine reine Seed-/Template-Klasse fuer eine vordefinierte Booking-Regel (Template-id 16). Sie haelt keinen Zustand und persistiert nichts selbst: sie liefert per statischer Factory ein `stdClass`, das aufgebaut ist „wie ein Datensatz aus der DB" (Tabelle `booking_rules`). Die eigentliche Regel reagiert auf das Event `optiondates_teacher_deleted` und versendet eine Mail an den im Event referenzierten User (den entfernten Trainer). Persistenz: keine eigene; das zurueckgegebene Objekt wird vom Regel-Seeding/Template-Loader (`rules_info`) verarbeitet. Kollaborateure: `get_string()`, Konsumenten der Template-Liste (Regel-Auswahl/Installation). `require_once .../lib.php` zieht die Modul-Library nur fuer den Konstanten-/String-Kontext.

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen des Templates als `"<template> - <optiondatesteacherdeleted>"`. **Seiteneffekte:** zwei `get_string(..., 'mod_booking')`-Aufrufe. **Rueckgabe:** string. **Bewertung:** A.

### `public static function return_template()` — public static
- **Zweck:** Baut das vollstaendige Regel-Template als `stdClass` zusammen: `rulejson` (Condition `select_user_from_event` mit `relateduserid`, Action `send_mail` mit lokalisiertem Subject/Template, Rule `rule_react_on_event` mit `boevent = optiondates_teacher_deleted`, `aftercompletion = 1`), eingebettet in das Aussen-Objekt mit `id = 16`, `rulename = rule_react_on_event`, JSON-kodiertem `rulejson`, `eventname`, `contextid = 1` (System), `useastemplate = 0`. **Seiteneffekte:** mehrere `get_string`-Aufrufe, `json_encode`. **Rueckgabe:** `(object)` mit DB-Record-Form. **Bewertung:** A — deklarativer Seed; Mail-Body referenziert Placeholder `{optiondatefromevent}`. Hardcodierte `contextid = 1` ist konventionell fuer Systemkontext-Templates.

### Triviale Properties
Zwei statische Properties als Konfigurations-Konstanten: `$templateid = 16` (Z.34), `$eventtype = 'rule_react_on_event'` (Z.37).

## Bewertungs-Resümee
Zustandslose, rein deklarative Seed-Klasse mit einer Factory und einem Namens-Helfer. Kein Kontrollfluss, keine Persistenz, kein Fehlerpfad — entsprechend wartungsarm und korrekt. Klassen-Score **A / P3**.
