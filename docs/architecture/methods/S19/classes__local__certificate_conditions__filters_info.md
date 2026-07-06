# filters_info — Methoden-Doku
**Datei:** `classes/local/certificate_conditions/filters_info.php` · **LOC:** 137 · **Subsystem:** S19 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S19_certificates.md)

## Klassenueberblick
`filters_info` ist die Discovery- und Form-Selector-Schicht fuer die Filter-Saeule. Sie findet alle Klassen im Namespace `local\certificate_conditions\filters` per `core_component`, baut das Dropdown `certificatefiltertype` (mit fuehrendem `norestriction`-Eintrag) und delegiert das Einhaengen der filterspezifischen Felder an den gewaehlten Filter. Gegenueber `conditions_info` zusaetzlich: ein optionaler Kompatibilitaets-Skip (`is_compatible_with_ajaxformdata`) und eine kompliziertere Default-Auswahl-Logik. Reine statische Helferklasse, keine eigene Persistenz. Kollaborateure: `core_component`, `MoodleQuickForm`, die `filters\*`-Handler (via `filter_interface`).

## Methoden

### `public static function add_filters_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public static
- **Zweck:** Baut den Filter-Typ-Selektor (inkl. `norestriction`) und haengt die Felder des gewaehlten Filters ein. **Seiteneffekte:** ueberspringt Filter, die `is_compatible_with_ajaxformdata()` mit `false` beantworten (Duck-Typing via `method_exists`); registriert NoSubmit-Button `btn_certificatefiltertype`, fuegt Select + unsichtbaren Submit hinzu, setzt Default, mutiert `$ajaxformdata['certificatefiltertype']`, ruft `add_filter_to_mform` des gewaehlten Filters. **Bewertung:** C — die Sentinel-Werte sind inkonsistent: die Dropdown-Optionen sind `norestriction` + Kurz-Klassennamen, der `$defaultfiltertype` ist aber `'0'` (Zeile 76) — ein Key, der nicht in den Optionen existiert. Bei leerem `ajaxformdata` wird `setDefault('certificatefiltertype', '0')` gesetzt, obwohl `'0'` keine waehlbare Option ist; der Browser faellt dann auf die erste Option (`norestriction`) zurueck. Funktioniert faktisch, ist aber fragil und divergiert von `conditions_info` (das `'0'` als echten Options-Key fuehrt).

### `public static function get_filters()` — public static
- **Zweck:** Liefert je eine frische Instanz aller Filter-Klassen im `filters`-Namespace. **Seiteneffekte:** `core_component::get_component_classes_in_namespace(...)`, instanziiert jede Klasse. **Rueckgabe:** Array von Filter-Objekten. **Bewertung:** A — die lokale Variable heisst `$conditions` (Copy-Paste aus `conditions_info`), inhaltlich korrekt.

### `public static function get_filter(string $name)` — public static
- **Zweck:** Liefert eine Filter-Instanz anhand des Kurz-Klassennamens. **Seiteneffekte:** `class_exists`-Pruefung, ggf. Instanziierung. **Rueckgabe:** Filter-Objekt oder `null`. **Bewertung:** A — FQCN via Konkatenation, durch `class_exists` abgesichert.

## Bewertungs-Resümee
Korrekte Discovery-/Selector-Schicht mit nuetzlichem Kompatibilitaets-Skip. Schwaechen sind die inkonsistente Default-Sentinel-Logik (`'0'` ist kein Dropdown-Key, anders als bei `conditions_info`) und ein verirrter Variablenname `$conditions`. Beides funktional unkritisch (Browser-Fallback rettet die UI). Klassen-Score **B / P3**.
