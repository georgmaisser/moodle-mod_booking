# db/access.php — Methoden-Doku (Capability-Definition)

**Datei:** `mod/booking/db/access.php` · **LOC:** 674 · **Subsystem:** S22 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S22_*.md)

## Klassenueberblick
Reine **deklarative Moodle-Capability-Definition** — keine Klasse, keine Funktion. Die Datei
fuellt das von Moodle erwartete Array `$capabilities` (Access-API). Jeder Eintrag definiert
eine Berechtigung (`mod/booking:*`) mit `captype` (read/write), `contextlevel`, optionalem
`riskbitmask`, `archetypes` (Default-Rollen-Zuweisung) und optionalem `clonepermissionsfrom`.
Konsument ist ausschliesslich der Moodle-Core (`accesslib`, beim Plugin-Install/Upgrade ueber
`update_capabilities()`); zur Laufzeit gelesen von `has_capability()`/`require_capability()`-
Aufrufen im gesamten Plugin. Keine Geschaeftslogik, kein DB-Zugriff in dieser Datei selbst.

## Methoden
**Keine Methoden/Funktionen vorhanden.** Datei ist ein statisches Konfigurations-Array.

### `$capabilities` (globales Array, 60+ Eintraege) — file-scope
- **Zweck:** Definiert saemtliche Berechtigungen des Plugins fuer das Moodle-RBAC-System.
- **Parameter / Rueckgabe:** Keine — wird per `include` vom Core eingelesen; der Core wertet
  das Array aus.
- **Seiteneffekte:** Keine zur Include-Zeit. Mittelbar: beim Install/Upgrade schreibt der Core
  daraus Zeilen in `mdl_capabilities` und legt Default-Rollen-Zuweisungen in `mdl_role_capabilities`
  an. `defined('MOODLE_INTERNAL') || die();` (Zeile 24) ist der uebliche Direktaufruf-Schutz.
- **Aufrufkette:** Eingelesen vom Moodle-Access-Subsystem (`get_cached_capabilities`,
  `update_capabilities`). Die definierten Strings werden ueberall im Plugin via
  `has_capability('mod/booking:...')` konsumiert.
- **Inhaltliche Gruppen:**
  - Kern-View/Booking: `view`, `choose`, `comment`, `managecomments`, `canseeinvisibleoptions`.
  - Options-Authoring: `addeditownoption` (Legacy-Name = nur Editieren, Zeile 77), `addoption`
    (separates Hinzufuegen, Zeile 86), `limitededitownoption`, `manageoptiondates`,
    `manageoptiontemplates`, `expertoptionform`, `reducedoptionform1..5`.
  - AI-/Agent-Skills (Zeile 95-247): `skill_mod_booking_*` — namensabgeleitetes Capability-Schema
    `<component>:skill_<name>` (Gate-2-Autorisierung des Agenten); read fuer Diagnose/Such-Skills,
    write fuer mutierende Skills. Konsistent mit der dokumentierten Zwei-Gate-Autorisierung.
  - Reporting/Responses: `readresponses`, `deleteresponses`, `downloadresponses`,
    `viewreports`, `managebookedusers`, `viewscheduledmails`, `editscheduledmails`,
    `viewperformance`, `editperformance`.
  - Rating: `viewrating`, `viewanyrating`, `viewallratings`, `rate`.
  - Slotbooking: `manageslotunavailability`, `moveslots`, `moveslotsself`.
  - System-/Admin-weit (`CONTEXT_SYSTEM`): `overrideboconditions`, `cansendmessages`,
    `canoverbook`, `editoptionformconfig`, `executebulkoperations`, `assigndeputies`,
    `calculateprices`, `bookanyone`, `editsemesters`, `canseenumberofbookings`, u.a.
  - Risk-Flags gesetzt: `RISK_SPAM` (comment/managecomments), `RISK_XSS` (addinstance),
    `RISK_PERSONAL` (viewanyrating, viewallratings, readallinstitutionusers).
- **Bewertung:** **A.** Idiomatische, gut strukturierte Access-Definition. Keine Logik =
  nichts zu testen ausser dem Core-Lint (`tool_checkcapability`/CI). Single-Responsibility erfuellt.

## Anmerkungen (keine echten Bugs)
- Kosmetische Einrueckungs-Inkonsistenzen: `bookallstudents` (Z. 406-414), `seealllisttoapprove`
  (Z. 623-629) sind um 4 Spalten verschoben. Kein funktionaler Effekt.
- `readallinstitutionusers` (Z. 351-353) schreibt `captype`+`contextlevel` in eine Zeile —
  Style-Abweichung, harmlos.
- Semantisch diskussionswuerdig (kein Bug): `viewperformance`/`viewreports`/`canseenumberofbookings`
  tragen teils `captype => 'write'` bzw. `'read'` uneinheitlich (z.B. `viewperformance` = write
  obwohl „view"); ist aber bewusst, da darunter teils editierbare Performance-Daten haengen.
- `reducedoptionform1..5` (Z. 512-535) ohne `archetypes` — bewusst, da nur per Settings/global
  zugewiesen.
