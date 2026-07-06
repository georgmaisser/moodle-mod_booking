# cancelbooking — Methoden-Doku
**Datei:** `classes/bo_actions/action_types/cancelbooking.php` · **LOC:** 91 · **Subsystem:** S04 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S04_*.md)

## Klassenueberblick
`cancelbooking` ist ein bo_action-Typ (erweitert `booking_action`), der die Buchung des aktuellen oder uebergebenen Users in einer Buchungsoption storniert, sobald die Aktion ausgeloest wird (typischerweise als After-Action nach einem anderen Buchungsschritt). Die Klasse haelt keinen Zustand; sie liefert nur das Form-Fragment (`add_action_to_mform`) und die Ausfuehrung (`apply_action`). Persistenz erbt sie aus `booking_action::save_action` (Options-JSON). Kollaborateure: `singleton_service::get_instance_of_booking_option`, `booking_option::user_delete_response`.

## Methoden

### `public function apply_action(stdClass $actiondata, int $userid = 0)` — public
- **Zweck:** Storniert die Buchungsantwort des Users in der durch `$actiondata->optionid`/`cmid` bezeichneten Option. **Seiteneffekte:** laedt die Option via Singleton; faellt bei leerem `$userid` auf `$USER->id` zurueck; ruft `$option->user_delete_response($userid)` → loescht/soft-deleted die Buchungsantwort, kann Warteliste nachruecken, Events/Benachrichtigungen ausloesen. **Rueckgabe:** `int` 1 — bricht alle folgenden After-Actions ab. **Bewertung:** A — schlank und eindeutig; der Abort-Status (1) ist hier sinnvoll, da nach einer Stornierung Folge-Aktionen meist unerwuenscht sind.

### `public static function add_action_to_mform(&$mform)` — public static
- **Zweck:** Fuegt dem Aktions-Formular ein Namensfeld und eine (per Default aktivierte) advcheckbox `boactioncancelbooking` hinzu. **Seiteneffekte:** mutiert `$mform`; setzt Default 1. **Rueckgabe:** void. **Bewertung:** A — minimalistisch. (Die Checkbox `boactioncancelbooking` wird in `apply_action` nicht ausgewertet — die Stornierung erfolgt bedingungslos; der Schalter ist faktisch dekorativ.)

## Bewertungs-Resümee
Kleinster und klarster Aktionstyp der Familie. Einziger kosmetischer Hinweis: die in `add_action_to_mform` angebotene `boactioncancelbooking`-Checkbox steuert nichts. Funktional korrekt und risikoarm. Klassen-Score **A / P3**.
