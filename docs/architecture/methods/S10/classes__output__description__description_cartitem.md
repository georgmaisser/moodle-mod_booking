# description_cartitem — Methoden-Doku
**Datei:** `classes/output/description/description_cartitem.php` · **LOC:** 38 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`description_cartitem extends description_base` ist die Beschreibungs-Strategy fuer die Warenkorb-Item-Ausgabe einer Buchungsoption. Sie enthaelt keine eigene Logik, sondern setzt lediglich Template (`bookingoption_description_cartitem`) und Param (`MOD_BOOKING_DESCRIPTION_CARTITEM`); das gesamte Render-Verhalten kommt aus `description_base`.

## Methoden
Keine eigenen Methoden — `render()` und Konstruktor werden von `description_base` geerbt.

### Triviale Properties
`$template = 'mod_booking/bookingoption_description_cartitem'`, `$param = MOD_BOOKING_DESCRIPTION_CARTITEM` (Z.27–37) — die einzigen Anpassungen gegenueber der Basis.

## Bewertungs-Resümee
Reine deklarative Strategy-Spezialisierung ohne eigene Logik; korrektes Wiederverwenden der Basis. Klassen-Score **A / P3**.
