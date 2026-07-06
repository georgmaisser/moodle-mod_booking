# description_teachers — Methoden-Doku
**Datei:** `classes/output/description/description_teachers.php` · **LOC:** 32 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_*.md)

## Klassenueberblick
`description_teachers` ist die Teacher-Variante der Beschreibungs-Strategy. Sie erbt von `description_base` und setzt als einzigen Eigenanteil das Property `$template` (`mod_booking/bookingoption_description_teachers`). Sie uebernimmt damit auch das geerbte Default-`$param` (`MOD_BOOKING_DESCRIPTION_WEBSITE`) — anders als die anderen Strategien definiert sie keinen eigenen Param. Persistenz: keine (reines Output-DTO). Kollaborateure: `description_base` (Konstruktor, `render()`).

## Methoden
Keine eigenen Methoden — die Klasse ueberschreibt nur das Template-Property und nutzt `description_base::render()` unveraendert.

### Triviale Properties
Ein protected Property: `$template` (Teacher-Template). Kein eigener `$param` (erbt `MOD_BOOKING_DESCRIPTION_WEBSITE` aus der Basis).

## Bewertungs-Resümee
Minimalste Strategy-Spezialisierung (nur Template-Override). Dass kein eigener Description-Param gesetzt wird, ist beabsichtigt/vertretbar (Teacher-Block nutzt die Website-Platzhalter-Semantik). Keine Auffaelligkeiten. Klassen-Score **A / P3**.
