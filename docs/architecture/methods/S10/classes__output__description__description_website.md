# description_website — Methoden-Doku
**Datei:** `classes/output/description/description_website.php` · **LOC:** 38 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_*.md)

## Klassenueberblick
`description_website` ist die Web-Variante der Beschreibungs-Strategy und die Default-Auspraegung. Sie erbt von `description_base` und setzt `$template` (`mod_booking/bookingoption_description`) und `$param` (`MOD_BOOKING_DESCRIPTION_WEBSITE`) — beide entsprechen den Default-Werten der Basis, werden hier aber explizit gesetzt. Die Render-Logik kommt unveraendert aus der Basis. Persistenz: keine (reines Output-DTO). Kollaborateure: `description_base` (Konstruktor, `render()`).

## Methoden
Keine eigenen Methoden — die Klasse ueberschreibt nur Properties und nutzt `description_base::render()` unveraendert.

### Triviale Properties
Zwei protected Properties: `$template` (`mod_booking/bookingoption_description`) und `$param` (`MOD_BOOKING_DESCRIPTION_WEBSITE`).

## Bewertungs-Resümee
Reiner Konfigurations-Override, der die Web-Defaults explizit fixiert. Keine eigene Logik, keine Auffaelligkeiten. Klassen-Score **A / P3**.
