# time_handler — Methoden-Doku
**Datei:** `classes/option/time_handler.php` · **LOC:** 65 · **Subsystem:** S02 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`time_handler` kapselt zwei kleine Zeit-Hilfen fuer Optionsformulare: das Liefern der Minuten-Schrittweite (Setting-abhaengig) und das "Aufrunden" eines Timestamps auf die volle Stunde. Kollaborateure: Moodle-Config (`get_config`) und `make_timestamp`.

## Methoden

### `set_timeintervall(): array` — public static
- **Zweck:** Gibt `['step' => 5]` zurueck, wenn das Booking-Setting `timeintervalls` gesetzt ist, sonst leeres Array.
- **Rueckgabe:** array (mform date-selector Options).
- **Seiteneffekte:** Config-Read `booking/timeintervalls`.
- **Aufrufkette:** Form-Definition von Zeitfeldern.
- **Bewertung:** A. (Tippfehler im Namen "timeintervall" ist kosmetisch.)

### `prettytime(int $timestamp, bool $nextfullhour = true): int` — public static
- **Zweck:** Nullt Minuten und optional schiebt um +3600s auf die naechste volle Stunde.
- **Parameter/Rueckgabe:** Timestamp + Flag → normalisierter Timestamp.
- **Seiteneffekte:** keine (nutzt `make_timestamp`, das die Server-TZ verwendet).
- **Aufrufkette:** Default-Wert-Berechnung fuer Start/Endzeit-Felder.
- **Bewertung:** A.

## Anmerkung
Triviale, fokussierte Klasse. Keine flagged-Methoden.
