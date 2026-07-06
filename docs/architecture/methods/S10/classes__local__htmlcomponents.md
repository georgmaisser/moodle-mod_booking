# htmlcomponents — Methoden-Doku
**Datei:** `classes/local/htmlcomponents.php` · **LOC:** 423 · **Subsystem:** S10 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S10_*.md)

## Klassenueberblick
Statische Sammlung von reinen HTML-Render-Helfern (alle Methoden `public static`), die ueber `html_writer` Bootstrap-Markup (Tabs, Pills/Earmarks, Collapsible, Modal, Delete-Confirmation, Editor) erzeugen. Keine eigene State-Haltung, keine DB-/Cache-/Event-Interaktion — reine View-Bausteine. Kollaborateure: Moodle-Core `html_writer`, `get_string`, `s`/`strip_tags`. Der Klassen-Doc-Block ist falsch (nennt die Klasse `scheduledmails`). Schwaechen: zwei nahezu identische, lange Tab-Renderer (Duplikat), hardcodierte Element-IDs (Kollisionsgefahr), hardcodierte englische Strings sowie ein echter Bug mit undefinierter Variable.

## Methoden

### `render_bootstrap_tabs(array $tabs, string $id = 'moodle-tabs'): string` — public static
- **Zweck:** Rendert ein BS4/BS5-kompatibles Tab-Set (Nav + Tab-Content) aus einem Array von `['title','body','active']`.
- **Parameter:** `$tabs` Liste von Tab-Definitionen; `$id` ID-Praefix.
- **Rueckgabe:** HTML-String (leer bei leerem `$tabs`).
- **Seiteneffekte:** Keine (rein funktional, nur `html_writer`/`strip_tags`).
- **Aufrufkette:** Statisch von Render-/Shortcode-Code aufgerufen; ruft `html_writer::*`, `strip_tags`.
- **Bewertung:** C — 81 LOC (`htmlcomponents.php:43`), grosse Strukturduplikation mit `render_bootstrap_earmarks` (`htmlcomponents.php:140`); tote Variable `$label` (Zeile 70 berechnet, nie genutzt — anders als in earmarks).

### `render_bootstrap_earmarks(array $tabs, string $id = 'moodle-earmarks'): string` — public static
- **Zweck:** Rendert links-vertikale, kompakte Pill-Tab-Navigation ("earmark"-Stil) inkl. inline `<style>`-Block.
- **Parameter:** `$tabs` Tab-Definitionen; `$id` ID-Praefix.
- **Rueckgabe:** HTML-String (leer bei leerem `$tabs`).
- **Seiteneffekte:** Keine; bettet inline CSS via `<style>`-Tag ein.
- **Aufrufkette:** Statisch aufgerufen; ruft `html_writer::*`, `strip_tags`.
- **Bewertung:** C — 113 LOC (`htmlcomponents.php:140`), groesstenteils 1:1-Duplikat von `render_bootstrap_tabs`; gemischte Verantwortung (HTML + inline CSS-String, `htmlcomponents.php:226`).

### `render_bootstrap_collapsible(string $headertext, string $bodytext): string` — public static
- **Zweck:** Rendert einen Collapse-Link mit zugehoerigem Card-Body.
- **Parameter:** `$headertext` Link-Text; `$bodytext` Inhalt.
- **Rueckgabe:** HTML-String.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Statisch; ruft `html_writer::*`.
- **Bewertung:** C — hardcodierte, feste Element-ID `pollurlplaceholders` (`htmlcomponents.php:271-291`): mehrere Instanzen auf einer Seite kollidieren/togglen sich gegenseitig; eingebettetes Roh-HTML fuer das Icon statt `html_writer`.

### `render_bootstrap_modal_with_body(string $html, $shortcodehash, $shortcodename): string` — public static
- **Zweck:** Rendert Trigger-Button + Bootstrap-Modal (xl) mit beliebigem Body-HTML.
- **Parameter:** `$html` Body-Inhalt; `$shortcodehash` ID-Suffix; `$shortcodename` fuer Modal-Titel (`get_string`).
- **Rueckgabe:** HTML-String.
- **Seiteneffekte:** Keine; `get_string('formmeasurementheading', 'booking', ...)`.
- **Aufrufkette:** Statisch; ruft `html_writer::*`, `get_string`.
- **Bewertung:** C — 51 LOC (`htmlcomponents.php:308`), hardcodierte englische Strings " Edit" und "close" (`htmlcomponents.php:319,347`) statt `get_string`; untypisierte Parameter `$shortcodehash`/`$shortcodename`.

### `render_bootstrap_collapsible_modal(string $collapseid, $valuesid): string` — public static
- **Zweck:** Rendert ein collapsible Notiz-Editor-Panel (Textarea + Save-Button).
- **Parameter:** `$collapseid` ID-Suffix; `$valuesid` Mess-/Datensatz-ID.
- **Rueckgabe:** HTML-String.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Statisch; ruft `html_writer::*`, `get_string`, `s`.
- **Bewertung:** D — **echter Bug:** `s($values->note ?? '')` (`htmlcomponents.php:405`) referenziert die undefinierte Variable `$values` (Parameter heisst `$valuesid`) → Notification/Warning, Textarea immer leer; hardcodierter String "Note" statt `get_string`.

## Triviale / einfache Akzessoren
### `render_bootstrap_collapsible_delete_confirmation(string $collapseid, $valuesid): string` — public static
- **Bewertung:** B — schlanke, klare Render-Funktion (Card mit Loesch-Button, `data-action=deletemeasurement`); nutzt `get_string`. Keine Seiteneffekte.
