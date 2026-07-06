# mod_booking — Architektur-Dokumentation: Master-Planungsdokument

> **Status:** Phase 1 (Draft / Planung) · **Stand:** 2026-06-28 · **Plugin-Version:** v9.4.0 (`2026062700`), Moodle 4.5+ (`requires 2024100700`)
>
> Dieses Dokument ist der **Bauplan für die Architektur-Doku**, nicht die Doku selbst. Es legt fest, _welche_ Dokumente entstehen, _wie_ sie strukturiert sind, _in welcher Reihenfolge_ sie befüllt werden und _nach welcher Methodik_ jede Datei, Klasse und Methode erfasst und bewertet wird.

---

## 0. Warum dieses Dokument existiert

`mod_booking` ist kein normales Modul, sondern eine über Jahre gewachsene Plattform:

| Kennzahl | Wert |
|---|---|
| PHP-Dateien gesamt | **2.984** |
| PHP-Zeilen gesamt | **~733.700** |
| Klassen-Dateien (`classes/`) | **674** |
| Top-Level-Entry-Scripts | **74** |
| Option-Felder (`option/fields/`) | **80** |
| Availability-Conditions (`bo_availability/`) | **50** (+ Subconditions) |
| Placeholders (`placeholders/`) | **73** |
| Events (`event/`) | **47** |
| External/Webservice-Klassen (`external/`) | **31** |
| Webservice-Definitionen (`db/services.php`) | **193** |
| Scheduled/Adhoc-Tasks (`task/`) | **19** |
| Output-Renderer (`output/`) | **42** |
| WB-Tables (`table/`) | **12** |
| AMD-JS-Module (`amd/src/`) | **54** (+ `vue3/`) |
| Test-Dateien (`tests/`) | **134** |

Die größten Einzeldateien (LOC):

| LOC | Datei |
|---|---|
| 5.279 | `classes/booking_option.php` |
| 2.940 | `classes/local/wizard/booking/booking_skill_support.php` |
| 2.391 | `classes/booking.php` |
| 2.350 | `classes/shortcodes.php` |
| 2.096 | `classes/table/bookingoptions_wbtable.php` |
| 1.923 | `classes/output/view.php` |
| 1.754 | `classes/booking_option_settings.php` |
| 1.632 | `classes/signinsheet/signinsheet_generator.php` |
| 1.606 | `classes/booking_answers/booking_answers.php` |
| 1.603 | `classes/bo_availability/bo_info.php` |

Eine Doku dieser Größe lässt sich **nicht in einem linearen Durchlauf** korrekt erstellen. Sie braucht ein festes Gerüst, eine reproduzierbare Erfassungsmethodik und eine getrennte Qualitäts-/Refactoring-Bewertung. Das definiert dieses Dokument.

---

## 1. Ziel & Nicht-Ziel

**Ziel**
- Eine vollständige, navigierbare Architektur-Doku unter `docs/architecture/`, die jedes Subsystem, jede relevante Datei/Klasse und (in der Tiefe) jede öffentliche/wesentliche Methode erfasst und _korrekt in das Gesamtbild einhängt_.
- Ein getrenntes **Qualitäts- & Refactoring-Register** unter `docs/blueprints/`, das pro Klasse/Methode Qualität, Risiken und Refactoring-Bedarf bewertet und priorisiert.

**Nicht-Ziel**
- Keine Code-Änderungen. Diese Doku ist rein beschreibend/bewertend (read-only gegenüber dem Plugin-Code).
- Keine Doppelung von Inline-PHPDoc — die Doku beschreibt _Architektur und Zusammenhang_, nicht jede Zeile.
- Keine Erfindung von „Soll-Architektur" — wir dokumentieren den **Ist-Zustand** und markieren Abweichungen/Schulden separat im Blueprint.

---

## 2. Deliverable-Struktur

### 2.1 `docs/architecture/` — die Architektur-Doku

```
docs/architecture/
├── 00_ARCHITECTURE_DOC_PLAN.md      ← dieses Dokument
├── 01_SYSTEM_OVERVIEW.md            ← grundsätzlicher Draft / 10.000-Fuß-Sicht
├── 02_DOMAIN_MODEL.md               ← Kern-Domänenobjekte & Datenmodell (install.xml)
├── 03_REQUEST_LIFECYCLE.md          ← Entry-Points → Routing → Rendering/WS
├── subsystems/
│   ├── S01_core_domain.md
│   ├── S02_option_fields.md
│   ├── S03_availability.md
│   ├── S04_booking_process_bookit.md
│   ├── S05_pricing_shoppingcart.md
│   ├── S06_booking_rules.md
│   ├── S07_campaigns.md
│   ├── S08_subbookings.md
│   ├── S09_messaging_placeholders.md
│   ├── S10_output_rendering.md
│   ├── S11_external_api.md
│   ├── S12_events.md
│   ├── S13_tasks.md
│   ├── S14_slotbooking.md
│   ├── S15_wizard_ai.md
│   ├── S16_forms.md
│   ├── S17_reporting.md
│   ├── S18_import_export.md
│   ├── S19_certificates.md
│   ├── S20_sync_enrolment.md
│   ├── S21_entry_scripts.md
│   ├── S22_db_layer.md
│   ├── S23_frontend_js.md
│   ├── S24_backup_restore.md
│   ├── S25_mobile.md
│   └── S26_privacy_gdpr.md
├── FILE_INDEX.md                    ← jede Datei → Subsystem-Zuordnung + 1-Zeiler
└── CLASS_INDEX.md                   ← jede Klasse → Verantwortung, Kollaborateure, Methodenliste
```

### 2.2 `docs/blueprints/` — die Qualitäts-/Refactoring-Analyse

```
docs/blueprints/
├── QUALITY_INDEX.md                 ← Master-Register: pro Klasse Score + Refactor-Priorität
├── METHOD_QUALITY_INDEX.md          ← Methoden-Ebene: auffällige Methoden, Smells, Score
├── REFACTORING_BACKLOG.md           ← priorisierte, umsetzbare Refactoring-Vorschläge
└── METRICS.md                       ← Rohmetriken (LOC, Komplexität, Fan-in/out) als Evidenz
```

> Trennung bewusst: `architecture/` = „Wie funktioniert es?", `blueprints/` = „Wie gut ist es und was sollte verbessert werden?". Beide referenzieren sich gegenseitig per Anker.

---

## 3. Subsystem-Dekomposition (der Architektur-Skelett)

Das gesamte Plugin wird auf **26 Subsysteme** abgebildet. Jede der 2.984 Dateien wird genau einem Subsystem zugeordnet (Mehrfachzuordnung nur als „berührt auch"-Querverweis).

| # | Subsystem | Kern-Artefakte | Rolle |
|---|---|---|---|
| **S01** | Core Domain | `booking.php`, `booking_option.php`, `booking_option_settings.php`, `booking_answers/`, `dates.php`, `semester*`, `price.php` | Zentrale Domänenobjekte: Instanz, Option, Buchungsantworten, Termine, Preise |
| **S02** | Option-Field-System | `option/fields/` (80), `option/`, `optionformconfig*` | Feld-basierte Form-/Persistenz-Architektur einer Buchungsoption |
| **S03** | Availability / Conditions | `bo_availability/bo_info.php`, `conditions/` (50), `subconditions/` | Gating-Engine: darf gebucht werden? (Zeit, Profil, Vorbedingungen …) |
| **S04** | Booking Process / bookit | `bo_actions/`, `bookit.js`, Prepages | Der eigentliche Buchungs-Flow inkl. Pre-Pages |
| **S05** | Pricing & Shopping-Cart | `price.php`, `pricecategories.php`, `shopping_cart/` | Preislogik + Integration `local_shopping_cart` |
| **S06** | Booking-Rules-Engine | `booking_rules/` (rules/conditions/actions/templates) | Event-getriebene Automatisierung (wenn X dann Y) |
| **S07** | Campaigns | `booking_campaigns/` | Zeit-/regelbasierte Preis-/Verfügbarkeits-Kampagnen |
| **S08** | Subbookings | `subbookings/`, `sb_types/` | Zusatzbuchungen (z. B. Zeitfenster, Items) zu einer Option |
| **S09** | Messaging & Placeholders | `message_controller.php`, `placeholders/` (73), `db/messages.php` | Benachrichtigungen + Platzhalter-Substitution |
| **S10** | Output / Rendering | `output/` (42), `templates/`, `table/` (12), `shortcodes.php` | Renderer, Mustache, WB-Tables, Shortcodes |
| **S11** | External / API | `external/` (31), `db/services.php` (193), `db/mobile.php` | Webservice-Layer |
| **S12** | Events | `event/` (47), `db/events.php` | Moodle-Events + Observer |
| **S13** | Tasks | `task/` (19), `db/tasks.php` | Scheduled + Adhoc-Tasks |
| **S14** | Slotbooking | `local/slotbooking/` (13), `slot*.php` | Zeitslot-Buchung (eigener Sub-Flow) |
| **S15** | Wizard / AI | `local/wizard/` (Skills, DTOs, Services) | KI-Skill-Support (Schnittstelle zu `bookingextension_agent`) |
| **S16** | Forms | `form/` (37), `mod_form.php`, `*_form.php` | Moodle-Forms & Dynamic Forms |
| **S17** | Reporting | `reportbuilder/`, `report2.php`, `performance.php`, `signinsheet/`, `*_report.php` | Reports, Anwesenheit, Performance |
| **S18** | Import / Export | `import/`, `importer/`, `importexcel*`, `importoptions.php`, CSV | Datenimport/-export |
| **S19** | Certificates | `local/certificate_conditions/` | Zertifikatsbedingungen & -ausstellung |
| **S20** | Sync & Enrolment | `local/sync/`, `enrollink.php` | Einschreibung in Kurse, Membership-Sync |
| **S21** | Entry-Scripts | 74 Top-Level `*.php` | HTTP-Einstiegspunkte (Pages/Actions) |
| **S22** | DB-Layer | `db/` (install.xml, upgrade, access, caches, log, subplugins) | Schema, Capabilities, Caches, Upgrade-Pfad |
| **S23** | Frontend (JS) | `amd/src/` (54), `vue3/` | Client-seitige Logik |
| **S24** | Backup / Restore | `backup/` | Moodle Backup/Restore-Handler |
| **S25** | Mobile | `local/mobile/`, `db/mobile.php` | Moodle-App-Integration |
| **S26** | Privacy / GDPR | `privacy/` | Privacy-Provider, Datenexport/-löschung |

---

## 4. Erfassungsmethodik (wie jede Phase abläuft)

Die Doku entsteht in **vier Phasen**. Jede Phase hat eine klare Definition von „fertig" und ein festes Schema, damit die Ausgabe über alle Subsysteme hinweg vergleichbar ist.

### Phase 1 — Grundgerüst (dieses Dokument + Overview)
- Dieses Planungsdokument.
- `01_SYSTEM_OVERVIEW.md`: 10.000-Fuß-Sicht, Subsystem-Landkarte, Haupt-Datenflüsse, externe Abhängigkeiten.
- **Fertig wenn:** Subsystem-Dekomposition steht und jeder Top-Level-Ordner ist zugeordnet.

### Phase 2 — Datei- & Klassen-Ebene
Pro Subsystem entsteht `subsystems/Sxx_*.md` nach festem Template (siehe §5). Parallel werden `FILE_INDEX.md` und `CLASS_INDEX.md` befüllt.

Pro **Klasse** wird erfasst:
- **Verantwortung** (1–2 Sätze, Single-Responsibility-Sicht)
- **Typ/Rolle** (Domänenobjekt, Service, Renderer, Form, Field, Condition, Task, Event, WS, DTO, Util …)
- **Kollaborateure** (welche Klassen ruft sie / wird sie gerufen) → Kopplungsbild
- **Persistenz** (welche DB-Tabellen, welche Caches)
- **Extension-Points** (Interfaces, Hooks, Subplugin-Verträge)
- **Methoden-Inventar** (Liste mit Sichtbarkeit + 1-Zeiler — die Tiefe kommt in Phase 3)

### Phase 3 — Methoden-Ebene
Pro **wesentlicher Methode** (public + nicht-triviale private/protected):
- Signatur, Zweck, Inputs/Outputs/Seiteneffekte
- Aufrufkette (von wo gerufen, was ruft sie)
- Besonderheiten (DB-Writes, Cache-Invalidierung, Events, externe Calls)

Triviale Getter/Setter werden gebündelt vermerkt, nicht einzeln seziert.
**Fertig wenn:** Jede öffentliche Methode jeder Klasse mind. einen Eintrag hat.

### Phase 4 — Qualitäts- & Refactoring-Index (→ `docs/blueprints/`)
Getrennt von der beschreibenden Doku. Bewertung nach festem Rubric (siehe §6).

---

## 5. Subsystem-Dokument-Template (`subsystems/Sxx_*.md`)

```markdown
# Sxx — <Subsystem-Name>

## Zweck & Grenzen
Was dieses Subsystem verantwortet — und was bewusst NICHT.

## Position im Gesamtsystem
Eingehende und ausgehende Abhängigkeiten (welche Subsysteme nutzen es / nutzt es).

## Schlüsselkonzepte
Die 3–7 Begriffe, ohne die man das Subsystem nicht versteht.

## Datenfluss
Sequenz/Diagramm der typischen Operation(en).

## Dateien & Klassen
| Datei | Klasse | Rolle | LOC | → Quality-Index |
|---|---|---|---|---|

## Persistenz
Tabellen, Caches, Konfiguration.

## Extension-Points
Interfaces, Subplugin-Verträge, Hooks.

## Bekannte Schulden (Verweis → Blueprint)
Kurzliste mit Anker in QUALITY_INDEX.md.
```

---

## 6. Qualitäts-Rubric (Phase 4)

Jede Klasse und jede auffällige Methode erhält einen Score **A–E** plus Refactor-Priorität **P0–P3**.

**Qualitäts-Score (A best … E schlecht)** — bewertet entlang:
1. **Single Responsibility** — macht die Einheit genau eine Sache?
2. **Größe/Komplexität** — LOC, zyklomatische Komplexität, Schachtelungstiefe.
3. **Kopplung** — Fan-in/Fan-out, globale Zugriffe, statische God-Calls.
4. **Testbarkeit** — Seiteneffekte, harte Abhängigkeiten, vorhandene Tests.
5. **Klarheit** — Benennung, PHPDoc, magische Werte, Duplikation.

**Refactor-Priorität:**
| Prio | Bedeutung |
|---|---|
| **P0** | Aktives Risiko (Korrektheit/Sicherheit/Perf) — sollte zeitnah angegangen werden |
| **P1** | Hohe Schuld, hohe Änderungsfrequenz — lohnt sich klar |
| **P2** | Schuld vorhanden, aber stabil/selten berührt — opportunistisch |
| **P3** | Kosmetisch / nice-to-have |

**Heuristik-Trigger (für Kandidaten-Vorauswahl):** Dateien > 1.000 LOC, Methoden > 80 LOC, > 4 Verschachtelungsebenen, Klassen mit > 30 Methoden, statische Aufruf-Cluster (`booking_option::`, `singleton_service::`), fehlende Tests bei hoher Fan-in.

> **Wichtig:** Score ist evidenzbasiert (Metriken in `METRICS.md`), nicht aus dem Bauch. Jeder P0/P1-Eintrag nennt die konkrete Stelle (`datei.php:zeile`).

---

## 7. Reihenfolge der Bearbeitung (Subsysteme nach Priorität)

Erfassung nach **Zentralität** (Fan-in × Domänen-Bedeutung), damit die wichtigsten Teile zuerst korrekt stehen:

1. S01 Core Domain · S02 Option-Fields · S03 Availability  → das Herz
2. S04 Booking-Process · S05 Pricing · S09 Messaging
3. S06 Rules · S10 Output · S11 External · S12 Events · S13 Tasks
4. S16 Forms · S17 Reporting · S22 DB-Layer
5. S07/S08/S14/S15/S18/S19/S20 (Feature-Subsysteme)
6. S21/S23/S24/S25/S26 (Ränder: Scripts, Frontend, Backup, Mobile, Privacy)

---

## 8. Skalierungs-Strategie (offene Entscheidung an Georg)

Phasen 2–4 bedeuten in Summe das Lesen + Bewerten von **674 Klassen und tausenden Methoden**. Das ist sinnvoll **parallelisierbar** (ein Erfassungs-Agent pro Subsystem/Datei-Cluster, danach Synthese). Diese Wahl betrifft Laufzeit und Token-Kosten erheblich und gehört dir — siehe die Rückfrage, die ich separat stelle. Bis dahin steht das Gerüst (Phase 1) und kann unabhängig reviewt werden.

---

## 9. Konventionen

- **Sprache:** Deutsch (Fließtext), Code-Bezeichner/Pfade original.
- **Verlinkung:** Relative Anker zwischen `architecture/` und `blueprints/`.
- **Quellenangabe:** Jede nicht-triviale Aussage referenziert `pfad.php:zeile`.
- **Ist-Zustand:** Beschreibung folgt dem Code v9.4.0, nicht der Wunsch-Architektur.
- **Read-only:** Kein Plugin-Code wird im Zuge der Doku geändert.

---

## 10. Fortschritts-Log

### 2026-06-29 (Lauf 3) — Phase 3 ✅ VOLLSTÄNDIG (Wellen 1–5) → 768 Method-Docs
- **Coverage:** **0 offen** — alle `classes/**/*.php` (691) + alle prozeduralen Core-Dateien ausserhalb `classes/` (77: Entry-Skripte, `backup/`, `locallib.php`, `cli/`, `version.php`) sind methoden-/flow-dokumentiert. Subplugins unter `bookingextension/` sind NICHT Teil des mod_booking-Core-Scopes.
- **Nachbereitung ✅:** `METHOD_QUALITY_INDEX.md` (in `docs/architecture/`, neben CLASS_INDEX/FILE_INDEX) aus allen 768 Docs auto-generiert: Score-/Prio-Verteilung (A=104·B=373·C=208·D=41·E=4 / P0=2·P1=47·P2=210·P3=471), **Hotlist P0/P1 & D/E (58 Klassen) mit belegten Findings** + voller Index nach Subsystem; in `README.md` verlinkt. Damit ist die „belegte" Refactor-/Bug-Liste committet gesichert (das separate `docs/blueprints/REFACTORING_BACKLOG.md` existiert in diesem Checkout nicht und wird per Georgs Commit-Scope-Regel nicht committet → Findings stattdessen dauerhaft im committeten Method-Quality-Index). Generator: `scratchpad/build_quality_index.py` (deterministisch, jederzeit re-runbar).
- **Welle 5 `760e1b697`:** 77 prozedurale Dateien → S21 (Entry-Skripte, 70), S24 (`backup/`, 4), S22 (`locallib`/`cli`/`version`, 3).

#### (Historie) Wellen 1–4: ALLE `classes/` erfasst → 691 Docs
- **Ausführung:** Workflow `phase3-architecture-docs` (`scratchpad/wf_phase3_doc.js`), 4 Wellen à ~20–31 parallele Agents (≤5 Dateien/Agent). Disjunkte Verzeichnisse; Agents schreiben ausschliesslich Method-Docs; Logging/Commit zentral.
- **Welle 1 `accf37811`:** S12 `event/` (47), S11 `external/` (30), S09 `placeholders/` (75).
- **Welle 2 `6951ff5ba`+`94d75423b`:** S03 `bo_availability/` (30), S06 `booking_rules/` (38), S02 `option/fields/` (71), S04 `bo_actions/` (5), S01 `booking_answers/scopes/` (6).
- **Welle 3 `9eac1ada0`:** S16 `form/` (40), S10 `output/`+`filters/` (47), S17 `reportbuilder/`+`checklist/`+`signinsheet/`+`table/` (16).
- **Welle 4 `1031256e0`:** `local/` (70, → S14/S15/S17/S19/S22/…), `task/` (19, S13), `plugininfo/`/`utils/`/`subbookings/` + Top-Level (`teachers_handler`, `GoogleUrlApi`).
- **STAND:** **691 Method-Docs · alle `classes/**/*.php` erfasst (0 offen).** Klassenbasierte Phase 3 = vollständig.
- **▶️ Welle 5 (offen):** 77 prozedurale Core-Dateien ausserhalb `classes/` (Entry-Skripte `view.php`/`edit_*.php`/`teachers*.php`, `lib.php`, `backup/`) — Rest der urspr. ~814er-Liste. Danach: `METHOD_QUALITY_INDEX.md` bauen + `REFACTORING_BACKLOG` von „Seed" auf „belegt" hochstufen.

#### (Detail Wellen 1+2 — Zwischenstand) → 486 getrackt
- **Ausführung:** Workflow `phase3-architecture-docs` (`scratchpad/wf_phase3_doc.js`), je Welle ~30 parallele Agents (≤5 Dateien/Agent). Disjunkte Verzeichnisse; Agents schreiben ausschliesslich Method-Docs (kein Code, keine Shared-Files); Logging/Commit zentral.
- **Welle 1 (committet `accf37811`):** S12 `event/` (47), S11 `external/` (30), S09 `placeholders/` (75).
- **Welle 2 (committet `6951ff5ba` partial + `94d75423b` complete):** S03 `bo_availability/` (30), S06 `booking_rules/` (38, rules/conditions/actions/templates), S02 `option/fields/` (71), S04 `bo_actions/` (5), S01 `booking_answers/scopes/` (6).
- **Stand:** **486 Method-Docs getrackt · 205 `classes/`-Dateien noch offen** (von 507 zu Beginn dieses Laufs).
- **Findings:** Pro-Methode/-Klasse direkt in den Docs vermerkt; Konsolidierung nach REFACTORING_BACKLOG erst nach vollständigem Lauf.
- **▶️ NÄCHSTE WELLEN (nach Usage-Reset, frisches Budget):** (3) `output/` + `form/` + `table/` + `reportbuilder/` + `signinsheet/` + `filters/` + `checklist/` (~104); (4) `local/` + `task/` + `utils/`/`plugininfo/`/`subbookings/`/`importer/`/`import/`/`entities/`/`customfield/`/`completion/` + 2 Top-Level (~101). Restliste jederzeit neu ableitbar: `find classes -name '*.php'` vs. vorhandene `methods/**`-Slugs.

### 2026-06-28 — Phase 1 ✅
- `00_ARCHITECTURE_DOC_PLAN.md` (dieses Dokument) + `01_SYSTEM_OVERVIEW.md` erstellt.
- 26-Subsystem-Dekomposition festgelegt, Methodik + Rubric fixiert.

### 2026-06-28 — Phase 2 ✅
- **Ausführung:** Multi-Agent-Workflow, 26 Agents (ein Subsystem/Agent), parallel. ~2,44 Mio Tokens, 577 Tool-Calls, ~17 Min.
- **Ergebnis:** 26 `subsystems/Sxx_*.md` (~8.000 Zeilen) + Indizes generiert:
  - `FILE_INDEX.md`, `CLASS_INDEX.md` (843 Dateien)
  - `../blueprints/QUALITY_INDEX.md`, `METRICS.md`, `REFACTORING_BACKLOG.md`
- **Coverage-Check:** 839 reale Code-Dateien, 835 direkt erfasst → 8 PHP-Orphans nachgetragen (u. a. `teachers_handler.php`, `GoogleUrlApi.php`, `completion/custom_completion.php`). Übrige „Orphans" = vue3 Build-/Coverage-/Test-Artefakte (außerhalb Architektur-Scope). 6 Cross-Listings dedupliziert (Primär-Subsystem gewählt).
- **Vorab-Qualität:** 6× P0, 41× P1, 171× P2, 199× P3 · Scores: 15× E, 64× D, 217× C, 407× B, 139× A.
- **Bonus:** 11 echte (funktionale) Bugs beim Erfassen entdeckt → `REFACTORING_BACKLOG.md §A`.

### 2026-06-29 (Lauf 2) — Phase 3 ▶️ Multi-Agent-Pass → 184/814
- **Ausführung:** 7 parallele Erfassungs-Agents (grosse Dateien) + 4 Dateien direkt erfasst. Restliche Top-Level-`classes/`-Dateien aus dem Lauf-1-Pointer abgearbeitet; einige Agent-Docs haben vorbestehende Stubs aus den urspruenglichen 166 idempotent ueberschrieben (daher +6 netto bei 11 geschriebenen Dateien).
- **Neu/aktualisiert (Datei → Subsystem):** `booking_bookit`→S01, `elective`→S01, `price`→S05, `observer`→S12, `enrollink`→S20, `message_controller`→S09, `shortcodes`→S10, `shortcodes_handler`→S10, `mybookings_table`→S10, `bookinginstancetemplatessettings_table`→S10, `subbookings`→S08.
- **Funktionale Findings (→ REFACTORING_BACKLOG, teils P0/P1):**
  - `price::calculate_price_with_bookingoptionsettings` ruft `key()` auf einer `stdClass` ohne `(array)`-Cast (`price.php:398`) → **PHP-8 TypeError** fuer jede nicht-leere Formel (settings-Pfad latent gebrochen; Form-Pfad castet korrekt `:313`). P1.
  - `price::get_price` Preiskategorie-Match via `strpos` (Teilstring, `price.php:927`) — „student" ⊂ „studentplus" → falsche Kategorie; `return_user_to_buy_for` ueberschreibt gecachten bookfor-User mit `$USER->id` (`:1019-1024`).
  - `elective`: unparametrisiertes SQL (`get_combine_array` `:262`, `return_credits_booked` `:394`, `return_credits_left` `:420-421` interpolieren ids) → **SQL-Bind-Verstoss P0/P1**; `$_GET['list']` ungefiltert in `return_credits_selected`; `enrol_booked_users_to_course` N+1 + `enrolmentstatus` prueft letzte Loop-Variable statt pro Option (`:543`).
  - `message_controller`: undefinierte Variable `$subject` im DIVERTED-Vermerk (`:1028`, Copy-Paste-Rest); `force_current_language` wird bei vorzeitigen returns (`:230`,`:330`) nicht zurueckgesetzt → **Sprach-Leak** in Request/Task; Cache-Thrashing (`setbackbookinginstances` pro Mail invalidiert, im Code selbst als „bad idea" markiert); stiller `\Exception`→`false`-Schlucker in `send_message_with_ical` (`:979`). D/P1.
  - `booking_bookit`: fehlplatzierte Klammer `if (!empty($extrabuttoncondition && ...))` (`:312`); falscher Cache-Key im Credit-Fehlerpfad `delete($cachekey)` statt `delete($userid)` (`:440`); uninitialisierte `$buttoncondition`; `answer_booking_option` nahezu wortgleich mit `booking_subbookit` (Duplikation bestaetigt). D/P1.
  - `subbookings`: toter Konstruktor (Cache-Read ohne Wirkung, `$id` nie gesetzt); `user_submit_response` ignoriert `$json`-Param → schreibt `''` (`:88`, Datenverlust).
  - `shortcodes::executeservice` (`shortcodes.php:1621`): **dynamischer `$args['service']::execute(...)` aus einem Shortcode-Argument**, nur per `is_siteadmin()` gegated, keine Klassen-Whitelist → breiteste P1-Angriffsflaeche. Plus Doppel-Konsum-By-Ref-Mutation in `allbookingoptions` (`:800/:802`) und doppelte Filter-SQL-Berechnung in `mycourselist` (`:1011/:1089`, verworfen → DB-Verschwendung). D/P1.
  - `enrollink` (S20): wirkungslose Doppel-Konsum-Pruefung (`===` gegen String-DB-Werte, `:219-221`) → Idempotenz der Platzverbuchung nicht gewaehrleistet; **fehlende Concurrency-Absicherung** auf `booking_answers.places` (Read-modify-write ohne Lock → Ueberbuchung); `get_bo_contextid()` liefert eine cmid statt contextid (`:130-134`); globaler `bo_info::set_enrollink_context`-Toggle ohne try/finally (`:387-389`). C/P1.
  - `observer::custom_field_changed` (`observer.php:423`): **site-weiter N+1 (Score E)** — iteriert ALLE Optionen der Site mit synchronen Kalender-Writes je Teacher im Event-Request; geschluckte Exception `:383`; `$event`-Shadowing `:515`.
  - `mybookings_table::col_text` N+1 (`get_cmid_from_optionid` pro Zeile, `:90`); `shortcodes_handler::validatecondition` toter `$requirespro`-Param; (Lauf 1) `potential_subscriber_selector::set_force_subscribed` ignoriert Param.
- **▶️ NAECHSTER WIEDERANLAUF:** Alle Top-Level-`classes/*.php` sind jetzt in Phase 3 erfasst. **Weiter mit Unterverzeichnis-Klassen nach Plan §7 — duenn besetzte Subsysteme zuerst:** S09 (`placeholders/` ~73 Klassen; akt. nur 1!), S11 (`external/` 31; akt. 1!), S12 (`event/` 47; akt. 1!), S06 (`booking_rules/` rules/conditions/actions; akt. 4), S04 (`bo_actions/`, Prepages; akt. 4), S05 (`pricecategories.php`, `shopping_cart/`; akt. 2), dann S16 (`form/`), S17 (`reportbuilder/`, `signinsheet/`). **Slug-Konvention:** `methods/Sxx/classes__<pfad ab classes/ mit / → __>.md` (z. B. `classes__placeholders__<name>.md`).

### 2026-06-29 (Lauf 1) — Phase 3 ▶️ Teil-Wiederanlauf (Einzel-Agent) → 178/814
- **Kontext:** Architektur-Doku lag nicht im `agent-release`-Worktree, sondern auf Branch `architecture-no-commit` (197 Dateien). Per `git checkout architecture-no-commit -- docs/architecture/` in den Worktree geholt (kein Code angefasst, kein Commit).
- **+12 Methoden-Docs in S01** (alle Top-Level-`classes/`-Klassen, die in Phase 3 noch fehlten):
  `booking_context_helper`, `places`, `booking_tags`, `semester`, `booking_user_selector_base`, `bookit_request_overrides`, `booking_potential_user_selector`, `booking_existing_user_selector`, `subscriber_selector_base`, `existing_subscriber_selector`, `potential_subscriber_selector`, `booking_subbookit`.
- **Funktionale Findings beim Erfassen (→ REFACTORING_BACKLOG-Kandidaten):**
  - `potential_subscriber_selector::set_force_subscribed($setting)` und Konstruktor ignorieren den Parameter und erzwingen `forcesubscribed=true` (`set_force_subscribed(false)` wirkungslos).
  - `booking_potential_user_selector::find_users()` interpoliert `optionid` direkt in die NOT-IN-Subquery (SQL-Stil/Injection-Antipattern; hier int aus Form-Kontext).
  - `booking_subbookit::render_bookit_template_data()` kann `$buttoncondition` uninitialisiert verwenden; `$renderprepagemodal` toter Parameter; `answer_booking_option()` nahezu Duplikat von `booking_bookit` + 2 auskommentierte Rechte-Check-Bloecke (offenes Authz-TODO).
- **▶️ NAECHSTER WIEDERANLAUF — verbleibende S01-Top-Level-`classes/`-Klassen, noch ohne `methods/S01/<slug>.md`:**
  `booking_bookit` (745 LOC, gross), `elective` (698), `message_controller` (→evtl. S09), `shortcodes` (2350, →S10), `shortcodes_handler` (→S10), `mybookings_table` (→S10/S17), `observer` (→S12), `price` (→S05), `subbookings` (→S08), `enrollink` (→S20), `bookinginstancetemplatessettings_table`. (Wo ein anderes Subsystem passt, Doc dort ablegen.) Danach Subsysteme S04–S26 nach Plan §7 abarbeiten. **Slug-Konvention:** `classes__<pfad mit / → __>.md`.

### 2026-06-28 — Phase 3 ⏸️ PAUSIERT bei 166/814
- **Ausführung:** Multi-Agent-Workflow „alles in einem Lauf", 276 Tasks (814 Dateien gebündelt: große solo, kleine in 6er-Gruppen). Jeder Agent schreibt eine Per-Klassen-Methoden-Doc nach `methods/Sxx/<datei>.md` + liefert Quality-Findings (Methoden Score C–E).
- **Auf Georgs Wunsch gestoppt** (Token-Budget; Wochen-Limit resettet Mo 29.06 12:00 Europe/Vienna).
- **Fertig: 166/814 Methoden-Docs** — Kern-Subsysteme stark abgedeckt: S03 (31), S01 (23), S15 (21), S02 (17), S10 (12). Diese Dateien bleiben auf Platte erhalten.
- **METHOD_QUALITY_INDEX.md noch NICHT gebaut** (kommt erst nach vollständigem Lauf, sonst unvollständig).

#### ▶️ Wiederanlauf Phase 3 (nach Reset)
- Doku-Dateien sind idempotent (Agent überschreibt). Sauberster Weg: **Rest-Task-Liste neu generieren** (nur die ~648 noch fehlenden Dateien — die ohne `methods/Sxx/<slug>.md`), daraus `wf_phase3_rest.js`, dann Workflow starten. Vermeidet Wiederholung der 166 fertigen.
- Alternativ (nur in derselben Session möglich): `Workflow({scriptPath: ".../wf_phase3.js", resumeFromRunId: "wf_f4330d0b-2ba"})` — nutzt den Agent-Cache; nach Session-/Tageswechsel nicht garantiert.
- Item-/Task-Listen liegen im Scratchpad (`phase3_items.json`, `phase3_tasks.json`), Generator-Logik im Verlauf.
- Nach vollständigem Lauf: `METHOD_QUALITY_INDEX.md` bauen + `REFACTORING_BACKLOG.md` von „Seed" auf „belegt" hochstufen + Methoden-Doc-Index verlinken.
```
