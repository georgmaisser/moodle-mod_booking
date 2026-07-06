# instance_description — Methoden-Doku
**Datei:** `classes/output/instance_description.php` · **LOC:** 83 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`instance_description` ist ein schlankes Renderable/Templatable-DTO fuer die Kopf-/Beschreibungsdaten einer Buchungsinstanz (Intro-Text, Dauer, Punkte, Organisatorname). Es kopiert lediglich vier Felder aus einem uebergebenen Settings-Objekt und reicht sie ans Template durch. Persistenz: keine. Kollaborateure: `renderer_base` (Templatable-Vertrag), Aufrufer liefern ein Settings-stdClass.

## Methoden

### `public function __construct($settings)` — public
- **Zweck:** Uebernimmt `intro` → `description`, `duration`, `organizatorname` direkt; setzt `points` nur, wenn `$settings->points` ungleich `'0.00'` ist (sonst `null`, also keine Anzeige). **Seiteneffekte:** keine. **Bewertung:** B — der String-Vergleich `!= '0.00'` ist fragil: ein Punktwert wie `'0'`, `'0.0'` oder `0` (numerisch) wuerde faelschlich als „vorhanden" gewertet; haengt an exakter DB-Formatierung. Fuer den ueblichen `DECIMAL(.., 2)`-Wert funktioniert es.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Templatable-Vertrag; liefert `description`, `duration`, `points`, `organizatorname` als flaches Array. **Rueckgabe:** Array fuer Mustache. **Bewertung:** A.

### Triviale Properties
Vier oeffentliche Properties (`description`, `duration`, `points`, `organizatorname`, Z.39–49) als Werte-Halter.

## Bewertungs-Resümee
Minimales Pass-Through-DTO ohne Logik bis auf die Punkte-Sichtbarkeit. Einzige Anmerkung ist der String-basierte `'0.00'`-Vergleich. Funktional unkritisch. Klassen-Score **A / P3**.
