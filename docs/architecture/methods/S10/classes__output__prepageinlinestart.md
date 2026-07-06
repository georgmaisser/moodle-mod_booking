# prepageinlinestart — Methoden-Doku
**Datei:** `classes/output/prepageinlinestart.php` · **LOC:** 115 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`prepageinlinestart` ist ein schlankes renderable/templatable DTO fuer die *inline gerenderte Startstufe* einer Prepage-Buchungskette. Wenn ein Shortcode `inlinestartpage="<conditionname>"` setzt, wird die Seite dieser Bedingung direkt (ohne Button-Klick) auf der Seite gezeigt; ein „Continue"-Button oeffnet danach Modal bzw. Inline-Collapse fuer die restlichen Prepages. Die Klasse haelt nur Werte (optionid, userid, vorgerendertes Condition-HTML, skipcondition, Anzahl Restseiten, Inline-Flag) und reicht sie via `export_for_template()` an Mustache durch. Persistenz: keine. Kollaborateure: nur die Render-Pipeline (`renderer_base`).

## Methoden

### `public function __construct(int $optionid, int $userid, string $conditionhtml, string $skipcondition, int $remainingpages, bool $useinline = false)` — public
- **Zweck:** Befuellt die sechs Properties 1:1 aus den Argumenten. **Seiteneffekte:** keine. **Bewertung:** A — reiner Value-Setter.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Erzeugt zwei kollisionsarme uniquids (eine fuer den Inline-Start-Bereich, eine fuer den Restseiten-Container) und gibt das Render-Array zurueck, inkl. abgeleitetem `hasremainingpages` (= `remainingpages > 0`). **Seiteneffekte:** `time()`, `random_int(1,1000)`, zwei `md5(...)`-Hashes (auf 16 Zeichen gekuerzt). **Rueckgabe:** Array mit `uniquid`, `remaininguniqid`, `optionid`, `userid`, `conditionhtml`, `skipcondition`, `remainingpages`, `hasremainingpages`, `useinline`. **Bewertung:** A — die zwei Hashes leiten sich aus identischem `optionid.time.rand`-Seed plus unterschiedlichem Suffix (`'start'`/`'remaining'`) ab, daher deterministisch verschieden; sauber.

## Bewertungs-Resümee
Vorbildliches reines DTO ohne Logik oder Seiteneffekte ausser ID-Generierung. Keine Auffaelligkeiten. Klassen-Score **A / P3**.
