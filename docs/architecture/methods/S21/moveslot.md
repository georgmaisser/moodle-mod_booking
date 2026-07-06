# moveslot — Methoden-Doku
**Datei:** `moveslot.php` · **LOC:** 87 · **Subsystem:** S21 · **Klassen-Score:** B / -
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse): Host-Seite fuer Manager, die Slots einer fremden Buchungsantwort bearbeiten (Slotbooking). Die Seite rendert nur den Container fuer das gemeinsame „Update booking"-DynamicForm (`slotupdate_form` + `slot_update_service`, Actor = Manager) und uebergibt per Data-Attributen den Kontext; Validierung, Routing (move/cancel/change), Persistenz und Events liegen vollstaendig im JS-Modul `mod_booking/condition/slotUpdate` und im Service. Kollaborateure: `slot_mover`, `$PAGE->requires->js_call_amd`, `$OUTPUT`, `html_writer`.

## Request-/Permission-Flow
1. **Bootstrap (Z.27-34):** `config.php`; Parameter `id` (cmid), `optionid`, `baid` (alle required); `get_course_and_cm_from_cmid` + `require_course_login`.
2. **Permission-Gate (Z.36-41):** Modul-Context; erlaubt bei `mod/booking:moveslots` ODER `mod/booking:updatebooking`; ist keines vorhanden, erzwingt `require_capability('mod/booking:moveslots')` die regulaere Fehlerseite.
3. **Move-Context (Z.43-45):** `slot_mover::get_move_context($optionid, $baid)`; `$owneruserid` = Userid der bearbeiteten Antwort (Manager handelt fuer fremden Nutzer).
4. **URLs/Page (Z.47-57):** baseurl (Selbst), returnurl auf `report.php`, Title/Heading/Context.
5. **JS-Init + Render (Z.62-86):** Container-id `booking-slotupdate-<optionid>`; `js_call_amd('mod_booking/condition/slotUpdate', 'init', [$containerid])`; Header/Heading; Wrapper-`div` mit Data-Attributen (`data-optionid`, `data-userid`=Owner, `data-baid`, `data-selfservice='0'`, `data-returnurl`), Form-Region, Submit-Button, Footer.

- **Seiteneffekte:** keine direkte Mutation auf dieser Seite (delegiert an JS/Service); registriert AMD-Modul; Seiten-Output.
- **Bewertung:** B — saubere Trennung: die Seite ist reiner Host mit korrektem doppeltem Capability-Gate (`moveslots` ODER `updatebooking`) und `require_capability`-Fallback. Owner-Userid stammt aus `slot_mover::get_move_context`, nicht aus einem manipulierbaren Request-Param. `data-selfservice='0'` markiert den Manager-Modus. Keine funktionalen Maengel auf Seiten-Ebene.

## Bewertungs-Resümee
Schmaler, klar dokumentierter DynamicForm-Host fuer den Manager-Slot-Update-Flow; Berechtigungen sauber geprueft, Mutationslogik bewusst ausgelagert. Klassen-Score **B / -**.
