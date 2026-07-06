# description_dates — Methoden-Doku
**Datei:** `classes/output/description/description_dates.php` · **LOC:** 32 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`description_dates extends description_base` ist die Beschreibungs-Strategy fuer die Termine-Ausgabe einer Buchungsoption. Sie ueberschreibt ausschliesslich das Template (`bookingoption_description_dates`) und erbt Konstruktor, Param (`MOD_BOOKING_DESCRIPTION_WEBSITE` aus der Basis) und das komplette Render-Verhalten von `description_base`.

## Methoden
Keine eigenen Methoden — alles wird von `description_base` geerbt.

### Triviale Properties
`$template = 'mod_booking/bookingoption_description_dates'` (Z.27–31) — die einzige Anpassung gegenueber der Basis; `$param` bleibt der Basis-Default.

## Bewertungs-Resümee
Minimalste Strategy-Spezialisierung (nur Template-Override). Anmerkung: Anders als die Geschwisterklassen setzt sie keinen eigenen `$param`, sondern uebernimmt den Website-Default der Basis — bewusst, da Termine im Website-Kontext gerendert werden. Klassen-Score **A / P3**.
