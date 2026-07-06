# pricecategories — Methoden-Doku
**Datei:** `pricecategories.php` · **LOC:** 73 · **Subsystem:** S21 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Admin-Einstiegspunkt (keine Klasse). Die Seite rendert das Preiskategorien-Verwaltungsformular im Admin-Bereich von mod_booking. Sie ist ein duenner Host: Konfiguration, Capability-Gating via `admin_externalpage_setup`, Instanziierung von `pricecategories_handler` und des DynamicForms `pricecategories_form`, und Ausgabe des vorgerenderten Formulars ueber das `output\pricecategories`-DTO und den mod_booking-Renderer. Kollaborateure: `pricecategories_handler` (Persistenz der Kategorien), `pricecategories_form` (DynamicForm), `output\pricecategories` (DTO), AMD-Modul `mod_booking/dynamicpricecategoriesform` (clientseitiges Submit), `$OUTPUT`/`$PAGE`/Renderer.

## Request-/Permission-Flow
1. `require_once config.php` + `adminlib.php`, lokaler `pricecategories_handler`-Include.
2. `require_login(0, false)` — angemeldeter Nutzer, kein Gast-Autologin (Kommentar „No guest autologin").
3. `admin_externalpage_setup('modbookingpricecategories')` — setzt Kontext, Pagelayout und prueft implizit die Admin-Berechtigung der registrierten externen Admin-Seite (eigentliches Capability-Gate).
4. `$PAGE`-Konfiguration: System-Kontext, Pagelayout `admin`, URL/Title/Heading.
5. Instanziiert `pricecategories_handler` (Variable `$handler` wird allerdings danach nicht weiterverwendet — siehe Bewertung) und `pricecategories_form`; `set_data_for_dynamic_submission()` befuellt das Formular fuer den DynamicForm-Lebenszyklus.
6. `$PAGE->requires->js_call_amd('mod_booking/dynamicpricecategoriesform', 'init', [...])` — verdrahtet das clientseitige Submit gegen die Form-Klasse.
7. Ausgabe: Header, Heading, Untertitel-String, dann `output\pricecategories($mform->render())` ueber `render_pricecategories()`, Footer.

## Bewertung
- **Seiteneffekte:** Seitenausgabe (HTML), JS-AMD-Registrierung, kein Schreibzugriff auf die DB im GET-Pfad (Persistenz laeuft asynchron ueber den DynamicForm-Submit, nicht hier).
- **Bewertung:** A — kompakter, idiomatischer Admin-Host. Kleiner Schoenheitsfehler: `$handler = new pricecategories_handler();` (Z.52) wird angelegt, aber nicht verwendet (die Form holt ihren eigenen Handler) — toter lokaler State. Funktional unkritisch.

## Bewertungs-Resümee
Sauberer, minimaler Admin-Einstiegspunkt fuer die Preiskategorien-Verwaltung; Gating ueber `admin_externalpage_setup`, eigentliche Logik in Handler/Form ausgelagert. Einzige Notiz: ungenutzte `$handler`-Instanz. Klassen-Score **A / -**.
