# description_mail — Methoden-Doku
**Datei:** `classes/output/description/description_mail.php` · **LOC:** 38 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_*.md)

## Klassenueberblick
`description_mail` ist die Mail-Variante der Beschreibungs-Strategy. Sie erbt von `description_base` und setzt ausschliesslich die beiden Konfigurations-Properties `$template` (`mod_booking/bookingoption_description_mail`) und `$param` (`MOD_BOOKING_DESCRIPTION_MAIL`). Die gesamte Render-Logik kommt unveraendert aus der Basis. Persistenz: keine (reines Output-DTO). Kollaborateure: `description_base` (Konstruktor, `render()`).

## Methoden
Keine eigenen Methoden — die Klasse ueberschreibt nur Properties und nutzt `description_base::render()` unveraendert.

### Triviale Properties
Zwei protected Properties: `$template` (Mail-Template) und `$param` (`MOD_BOOKING_DESCRIPTION_MAIL`).

> Hinweis: Der PHPDoc-`@var` von `$template` lautet `int`, obwohl der typisierte Property `string` ist — kosmetische Doc-Inkonsistenz, kein funktionaler Defekt.

## Bewertungs-Resümee
Minimaler Konfigurations-Override ohne eigene Logik; sauberer Einsatz des Strategy-/Template-Method-Musters. Keine Auffaelligkeiten. Klassen-Score **A / P3**.
