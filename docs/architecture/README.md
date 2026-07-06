# mod_booking — Architektur-Dokumentation

> **Plugin:** mod_booking v9.4.0 (Moodle 4.5+) · **Stand:** 2026-06-28
> Vollständige, navigierbare Architektur-Doku. Erstellt in 4 Phasen (siehe [Master-Plan](00_ARCHITECTURE_DOC_PLAN.md)).

## Einstieg

| Dokument | Inhalt |
|---|---|
| [00_ARCHITECTURE_DOC_PLAN.md](00_ARCHITECTURE_DOC_PLAN.md) | Master-Plan: Methodik, 26-Subsystem-Dekomposition, Templates, Qualitäts-Rubric |
| [01_SYSTEM_OVERVIEW.md](01_SYSTEM_OVERVIEW.md) | 10.000-Fuß-Sicht: Subsystem-Landkarte, Domänenmodell, Datenflüsse, Abhängigkeiten |
| [FILE_INDEX.md](FILE_INDEX.md) | Jede der 843 Code-Dateien → Subsystem, Rolle, LOC |
| [CLASS_INDEX.md](CLASS_INDEX.md) | Jede Klasse → Verantwortung, Methodenzahl, Vorab-Qualität |
| [METHOD_QUALITY_INDEX.md](METHOD_QUALITY_INDEX.md) | Phase-3-Method-Docs (768) aggregiert: Score-/Prio-Verteilung, P0/P1-Hotlist mit Belegen, Index nach Subsystem |
| [methods/](methods/) | **Phase 3:** Per-Klasse/-Datei Methoden-Doku (jede Methode), 768 Dateien |

## Subsysteme

| # | Doc | # | Doc |
|---|---|---|---|
| S01 | [Core Domain](subsystems/S01_core_domain.md) | S14 | [Slotbooking](subsystems/S14_slotbooking.md) |
| S02 | [Option-Fields](subsystems/S02_option_fields.md) | S15 | [Wizard / AI](subsystems/S15_wizard_ai.md) |
| S03 | [Availability](subsystems/S03_availability.md) | S16 | [Forms](subsystems/S16_forms.md) |
| S04 | [Booking-Process / bookit](subsystems/S04_booking_process_bookit.md) | S17 | [Reporting](subsystems/S17_reporting.md) |
| S05 | [Pricing & Shopping-Cart](subsystems/S05_pricing_shoppingcart.md) | S18 | [Import / Export](subsystems/S18_import_export.md) |
| S06 | [Booking-Rules](subsystems/S06_booking_rules.md) | S19 | [Certificates](subsystems/S19_certificates.md) |
| S07 | [Campaigns](subsystems/S07_campaigns.md) | S20 | [Sync & Enrolment](subsystems/S20_sync_enrolment.md) |
| S08 | [Subbookings](subsystems/S08_subbookings.md) | S21 | [Entry-Scripts](subsystems/S21_entry_scripts.md) |
| S09 | [Messaging & Placeholders](subsystems/S09_messaging_placeholders.md) | S22 | [DB-Layer](subsystems/S22_db_layer.md) |
| S10 | [Output / Rendering](subsystems/S10_output_rendering.md) | S23 | [Frontend (JS)](subsystems/S23_frontend_js.md) |
| S11 | [External / API](subsystems/S11_external_api.md) | S24 | [Backup / Restore](subsystems/S24_backup_restore.md) |
| S12 | [Events](subsystems/S12_events.md) | S25 | [Mobile](subsystems/S25_mobile.md) |
| S13 | [Tasks](subsystems/S13_tasks.md) | S26 | [Privacy / GDPR](subsystems/S26_privacy_gdpr.md) |

## Qualität & Refactoring (separat)

Liegt unter [`../blueprints/`](../blueprints/):

| Dokument | Inhalt |
|---|---|
| [QUALITY_INDEX.md](../blueprints/QUALITY_INDEX.md) | Pro Klasse: Score A–E + Refactor-Prio P0–P3 + Schulden mit `datei:zeile` |
| [REFACTORING_BACKLOG.md](../blueprints/REFACTORING_BACKLOG.md) | Priorisierte, umsetzbare Refactorings + entdeckte echte Bugs |
| [METRICS.md](../blueprints/METRICS.md) | Roh-Evidenz: Score-/Prio-Verteilung, Top-LOC, Top-Methodenzahl |

## Status

| Phase | Stand |
|---|---|
| 1 — Gerüst & Übersicht | ✅ |
| 2 — Datei- & Klassen-Ebene (26 Subsysteme, 843 Dateien) | ✅ |
| 3 — Methoden-Ebene (jede Methode) | ⏸️ **pausiert bei 166/814** (Kern-Subsysteme abgedeckt) — Forts. nach Wochen-Reset (Mo 29.06 12:00) |
| 4 — Qualitäts-/Refactoring-Vertiefung | 🟡 Seed vorhanden, Vertiefung mit Phase 3 |

> **Phase-3-Methoden-Docs** liegen unter [`methods/Sxx/`](methods/) — 166 Dateien fertig (Stand 2026-06-28). Wiederanlauf: siehe [Master-Plan §10](00_ARCHITECTURE_DOC_PLAN.md#10-fortschritts-log).
