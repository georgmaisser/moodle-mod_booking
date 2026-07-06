# col_action — Methoden-Doku
**Datei:** `classes/output/col_action.php` · **LOC:** 73 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`col_action` ist ein minimales Renderable/Templatable-DTO fuer die Aktions-Spalte (Buchungsoptionen-Tabelle / shopping-cart-Button „Add to cart"). Es haelt nur eine Options-`id` sowie Default-Werte fuer `label` und CSS-`class` und reicht sie ans Template durch. Keine Persistenz, keine Kollaborateure ausser dem Mustache-Template. Score-Hinweis aus CLASS_INDEX: DTO, A.

## Methoden

### `public function __construct(int $id)` — public
- **Zweck:** „Dummy"-Konstruktor; speichert die uebergebene Options-`id`.
- **Seiteneffekte:** Setzt `$this->id`.
- **Bewertung:** A — trivial.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Liefert `id`, `label`, `class` und das fest verdrahtete `componentname => 'mod_booking'` ans Template.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** Array fuer das Mustache-Template.
- **Bewertung:** A — trivialer Pass-through.

### Triviale Properties
`label` (Default `'Add to cart'`), `class` (Default `'btn btn-primary'`), `id` (Default `null`). Das Default-`label` ist ein nicht ueber `get_string` lokalisierter, hartcodierter englischer String — in der Praxis wird es vom Template/Aufrufer ueberschrieben.

## Bewertungs-Resümee
Triviales Pass-through-DTO ohne Logik oder Persistenz. Einzig anmerkbar: das hartcodierte, unlokalisierte Default-`label`. Klassen-Score **A / P3**.
