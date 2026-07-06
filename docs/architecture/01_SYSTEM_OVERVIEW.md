# mod_booking — System-Übersicht (grundsätzlicher Draft)

> **Status:** Phase 1 (Draft) · **Stand:** 2026-06-28 · **Version:** v9.4.0 · siehe [Master-Plan](00_ARCHITECTURE_DOC_PLAN.md)
>
> 10.000-Fuß-Sicht: Was ist mod_booking, wie ist es grob aufgebaut, wie fließen die Daten, woran hängt es. Die Tiefe (Datei/Klasse/Methode) folgt in den Subsystem-Dokumenten.

---

## 1. Was ist mod_booking?

`mod_booking` ist ein **Moodle-Aktivitätsmodul** für Buchungs-/Anmeldungs-Szenarien: Kurse, Seminare, Termine, Plätze, Wartelisten, Preise und Zahlungen. Es ist weit über ein einfaches „Anmelde-Tool" hinausgewachsen zu einer **Buchungs-Plattform** mit eigener Preis-Engine, regelbasierter Automatisierung, Verfügbarkeits-Logik, Zeitslot-Buchung, Reporting, KI-Assistenz und einer eigenen Subplugin-Erweiterungs-Schicht.

- **Komponente:** `mod_booking`, Release **9.4.0** (`version 2026062700`)
- **Plattform:** Moodle **4.5+** (`requires 2024100700`)
- **Harte Abhängigkeit:** `local_wunderbyte_table` (≥ `2026061801`) — das Tabellen-Framework für alle Listenansichten
- **Subplugin-Typ:** `bookingextension` (unter `mod/booking/bookingextension/`) — u. a. der KI-Agent
- **Optionale Integrationen:** `local_shopping_cart` (Checkout/Zahlung), `local_entities` (Orte/Equipment), `tool_certificate` (Zertifikate), `core_ai`/LiteLLM (Agent)

---

## 2. Subsystem-Landkarte

Das Plugin gliedert sich in **26 Subsysteme** (vollständige Tabelle + Datei-Zuordnung im [Master-Plan §3](00_ARCHITECTURE_DOC_PLAN.md#3-subsystem-dekomposition-der-architektur-skelett)). Grob geordnet nach Schichten:

```
                          ┌─────────────────────────────────────────────┐
   EINSTIEG               │ S21 Entry-Scripts (74×)   S11 External/API   │
                          │ S23 Frontend JS/Vue       S25 Mobile         │
                          └───────────────┬─────────────────────────────┘
                                          │
                          ┌───────────────▼─────────────────────────────┐
   PRÄSENTATION           │ S10 Output/Rendering  S16 Forms  S17 Report  │
                          │ S09 Messaging/Placeholders                   │
                          └───────────────┬─────────────────────────────┘
                                          │
   ┌──────────────────────────────────────▼──────────────────────────────────┐
   │ KERN-DOMÄNE                                                              │
   │  S01 Core (booking / booking_option / booking_answers / dates / price)  │
   │  S02 Option-Field-System (80 Felder)                                    │
   │  S03 Availability/Conditions (50)   S04 Booking-Process/bookit          │
   │  S05 Pricing & Shopping-Cart        S08 Subbookings   S14 Slotbooking   │
   └──────────────────────────────────────┬──────────────────────────────────┘
                                          │
                          ┌───────────────▼─────────────────────────────┐
   AUTOMATISIERUNG        │ S06 Rules-Engine  S07 Campaigns  S12 Events  │
                          │ S13 Tasks  S19 Certificates  S20 Sync/Enrol  │
                          │ S15 Wizard/AI                                │
                          └───────────────┬─────────────────────────────┘
                                          │
                          ┌───────────────▼─────────────────────────────┐
   FUNDAMENT              │ S22 DB-Layer (40 Tabellen, Caches, Access)   │
                          │ S18 Import/Export  S24 Backup  S26 Privacy   │
                          └─────────────────────────────────────────────┘
```

---

## 3. Kern-Domänenmodell (Kurzfassung)

Drei Objekte bilden das Rückgrat; Details in [02_DOMAIN_MODEL.md](02_DOMAIN_MODEL.md) (Phase 2):

- **`booking`** (`classes/booking.php`, Tabelle `booking`) — die Modul-Instanz auf einer Kursseite. Container für Optionen, hält Instanz-Settings.
- **`booking_option`** (`classes/booking_option.php`, Tabelle `booking_options`) — eine buchbare Einheit (Kurs/Termin/Platzkontingent). Mit **5.279 LOC die zentrale, aber überladene Klasse** des Plugins.
- **`booking_answers`** (`classes/booking_answers/`, Tabelle `booking_answers`) — die Buchungen/Anmeldungen der Nutzer auf Optionen (gebucht, Warteliste, reserviert …).

Ergänzt durch: **`booking_option_settings`** (gecachtes Settings-DTO einer Option), **`dates.php`** (Termine/`booking_optiondates`), **`price.php`** + `booking_prices`/`booking_pricecategories` (Preise), **`semester`** (`booking_semesters`/`booking_holidays`).

**40 DB-Tabellen** gesamt (vollständige Liste in [02_DOMAIN_MODEL.md](02_DOMAIN_MODEL.md)), gruppiert:
- *Kern:* `booking`, `booking_options`, `booking_answers`, `booking_optiondates`, `booking_teachers`, `booking_category`
- *Preise:* `booking_prices`, `booking_pricecategories`, `booking_campaigns`
- *Slotbooking:* `booking_slot_config`, `booking_slot_rule`, `booking_slot_rule_price`, `booking_slot_moves`, `booking_slot_student_teacher`, `booking_teacher_unavailability`
- *Automatisierung:* `booking_rules`, `booking_cert_cond(_item)`, `booking_sync_rules`, `booking_sync_attempts`
- *Subbookings:* `booking_subbooking_options`, `booking_subbooking_answers`, `booking_combinations`
- *Sonstiges:* `booking_customfields`, `booking_tags`, `booking_ratings`, `booking_semesters`, `booking_holidays`, `booking_history`, `booking_form_config`, `booking_enrollink_*`, `booking_performance_measurements`, …

---

## 4. Haupt-Datenflüsse

### 4.1 Eine Option buchen (der zentrale Flow)
```
User klickt „Buchen" (bookit.js / WS)
   → bo_info::is_available()            [S03] prüft alle Conditions (Zeit, Profil, Vorbedingungen …)
   → bo_actions / Pre-Pages             [S04] ggf. mehrstufiger Flow (Preis, Bedingungen, Bestätigung)
   → price::get_price()                 [S05] Preis ermitteln (Kategorie/Kampagne)
   → [optional] local_shopping_cart     [S05] Checkout/Zahlung
   → booking_option::book / answers     [S01] Buchungsantwort schreiben (gebucht/Warteliste)
   → Events feuern                      [S12] z. B. bookingoption_booked
   → Rules-Engine reagiert              [S06] Bedingung→Aktion (Mail, Enrol …)
   → Tasks (adhoc/scheduled)            [S13] Mails, Kurs-Einschreibung
   → Messaging/Placeholders             [S09] Benachrichtigung mit Substitution
```

### 4.2 Eine Option anlegen/bearbeiten
```
editoptions.php / WS  → mod_form + form/ + option/fields/ (80 Felder)   [S16/S02]
   → jedes Feld: definition() / save_data() / set_data()   feldweise Persistenz
   → booking_option::update()           [S01] Schreiben + Cache-Purge
   → Events / Rules                     [S12/S06]
```

### 4.3 Anzeige (Listen)
```
view.php / Shortcode → output/view.php  [S10]
   → table/bookingoptions_wbtable (local_wunderbyte_table)  Server-seitige Tabelle
   → pro Zeile: Verfügbarkeit (S03), Preis (S05), Buttons (bookit, S04)
```

### 4.4 Automatisierung (event-getrieben)
```
Beliebiges Event [S12] → booking_rules [S06]
   → rule (z. B. „X Tage vor Start") → condition → action (Mail, Status, Enrol)
   → adhoc-Task [S13] führt aus → Messaging [S09]
```

---

## 5. Erweiterungs-Architektur (Extension-Points)

mod_booking ist bewusst auf **Pluralität gleichartiger Bausteine** ausgelegt — jeder Typ ist eine registrierte Familie austauschbarer Klassen, meist über ein Interface + Verzeichnis-Scan:

| Familie | Verzeichnis | Anzahl | Vertrag |
|---|---|---|---|
| Option-Felder | `classes/option/fields/` | 80 | `fields_info`-Interface (definition/save/set_data) |
| Availability-Conditions | `classes/bo_availability/conditions/` | 50 | `bo_condition`-Interface |
| Placeholders | `classes/placeholders/placeholders/` | 73 | `placeholders_info` |
| Booking-Rules (rules/cond/actions) | `classes/booking_rules/` | ~30 | `booking_rule` / `_condition` / `_action` |
| Events | `classes/event/` | 47 | `\core\event\base` |
| External/WS | `classes/external/` | 31 | `external_api` |
| Tasks | `classes/task/` | 19 | `scheduled_task` / `adhoc_task` |
| Subbooking-Typen | `classes/subbookings/sb_types/` | 3 | `booking_subbooking` |
| AI-Skills | `classes/local/wizard/options/skills/` | 21 | Skill-Base (Agent) |
| **Subplugins** | `bookingextension/` | — | `bookingextension`-Typ (z. B. `bookingextension_agent`) |

> Diese „Familien" sind der wichtigste Architektur-Hebel: Neue Funktionalität entsteht meist als _weitere Datei in einem dieser Ordner_, nicht durch Änderung der Kernklassen. Das hält Ränder erweiterbar — drückt aber Komplexität in die Kernklassen (`booking_option`, `bo_info`), die alle Familien orchestrieren.

---

## 6. Externe Abhängigkeiten & Grenzen

| Richtung | System | Zweck |
|---|---|---|
| **hart** | `local_wunderbyte_table` | Alle Listen/Tabellen (Server-seitig, Filter, Lazy-Load) |
| optional | `local_shopping_cart` | Warenkorb, Checkout, Zahlung, Storno/Refund |
| optional | `local_entities` | Orte & Equipment/Kapazität |
| optional | `tool_certificate` | Zertifikate (`local/certificate_conditions/`) |
| optional | `core_ai` / LiteLLM | KI-Agent (`bookingextension_agent`, via `local/wizard/`) |
| Moodle-Core | Events, Tasks, Forms, Backup, Privacy, Reportbuilder, Mobile | Standard-Subsysteme |

---

## 7. Bekannte Architektur-Spannungen (Vorab-Sicht, Details → Blueprints)

Schon auf Übersichtsebene sichtbar — wird in [QUALITY_INDEX.md](../blueprints/QUALITY_INDEX.md) (Phase 4) belegt & priorisiert:

1. **God-Klasse `booking_option` (5.279 LOC)** — orchestriert Felder, Buchung, Persistenz, Cache, Events. Hoher Fan-in, schwer testbar. Top-Refactor-Kandidat.
2. **Große statische/God-Helfer** — `booking.php` (2.391), `shortcodes.php` (2.350), `booking_option_settings.php` (1.754): viel statischer Zustand & Caching.
3. **`bookingoptions_wbtable` (2.096 LOC)** + Per-Zeilen-Render — bekannte N+1/Perf-Hotspots (vgl. Perf-Audit-Memo).
4. **Wizard/AI-Schicht (`booking_skill_support` 2.940 LOC)** — sehr groß, aber jung; Grenze zu `bookingextension_agent` ist sensibel (Engine-Grenze beachten).
5. **Slotbooking** als teils paralleler Sub-Flow (`moveslot.php`/`rebookslot.php` leben neben dem neuen `slotupdate`-Form) — Migrations-Restschuld.

> Diese Liste ist eine _Hypothese aus Metriken_, kein Urteil — Phase 4 prüft jede Stelle am Code und vergibt Score/Priorität evidenzbasiert.

---

## 8. Nächste Schritte

- **Phase 2** befüllt `subsystems/Sxx_*.md` + `FILE_INDEX.md` + `CLASS_INDEX.md` in der Reihenfolge aus [Master-Plan §7](00_ARCHITECTURE_DOC_PLAN.md#7-reihenfolge-der-bearbeitung-subsysteme-nach-priorität).
- **Phase 3** geht auf Methoden-Ebene.
- **Phase 4** erstellt das Qualitäts-/Refactoring-Register unter `docs/blueprints/`.
- Vor Start von Phase 2 steht die **Skalierungs-Entscheidung** (sequenziell vs. parallel/Multi-Agent) — siehe Rückfrage.
