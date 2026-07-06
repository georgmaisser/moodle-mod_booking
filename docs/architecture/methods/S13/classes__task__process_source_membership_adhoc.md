# process_source_membership_adhoc — Methoden-Doku
**Datei:** `classes/task/process_source_membership_adhoc.php` · **LOC:** 55 · **Subsystem:** S13 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S13_tasks.md)

## Klassenueberblick
`process_source_membership_adhoc` ist ein duenner `\core\task\adhoc_task`-Adapter, der genau eine Cohort-/Group-Mitgliedschaftsaenderung fuer den Booking-Enrolment-Sync verarbeitet, indem er den Custom-Data-Payload validiert und an `\mod_booking\local\sync\booking_enrolment::process_source_membership()` delegiert. Keine eigene Persistenz; die gesamte Fachlogik liegt im Sync-Subsystem (S20). Custom-Data: `sourcetype`, `sourceid`, `userid`, optional `membershipadded`. Kollaborateure: `booking_enrolment` (S20).

## Methoden

### `public function get_name()` — public
- **Zweck:** Sichtbarer Task-Name. **Seiteneffekte:** `get_string('taskprocesssourcemembershipsyncadhoc', 'mod_booking')`. **Rueckgabe:** lokalisierter String. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Validiert den Payload und stoesst die Verarbeitung der einzelnen Mitgliedschaftsaenderung an. **Seiteneffekte:** keine direkten — `get_custom_data()` lesen, dann Delegation an `booking_enrolment::process_source_membership((string)sourcetype, (int)sourceid, (int)userid, (bool)membershipadded)`; bei unvollstaendigem Payload (`empty`-Guard fuer `sourcetype`/`sourceid`/`userid`) stiller Abbruch via `return`. **Rueckgabe:** void. **Bewertung:** A — saubere, typgecastete Delegation mit Eingangsvalidierung; das vierte Argument `!empty($data->membershipadded)` defaulted bei fehlendem Feld korrekt auf `false`.

## Bewertungs-Resümee
Vorbildlicher, minimalistischer Adhoc-Adapter: validiert, castet, delegiert. Keine Fachlogik, keine Seiteneffekte ueber die Delegation hinaus, keine erkennbaren Maengel. Klassen-Score **A / P3**.
