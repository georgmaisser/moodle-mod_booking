# conditions_info — Methoden-Doku
**Datei:** `classes/local/certificate_conditions/conditions_info.php` · **LOC:** 111 · **Subsystem:** S19 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S19_certificates.md)

## Klassenueberblick
`conditions_info` ist die Discovery- und Form-Selector-Schicht fuer die Condition-Handler des Zertifikats-Frameworks. Sie findet alle Klassen im Namespace `local\certificate_conditions\conditions` per `core_component`, baut daraus das Dropdown `certificateconditiontype` (mit NoSubmit-Button fuer den AJAX-Reload des Formulars) und delegiert das Einhaengen der logikspezifischen Felder an den ausgewaehlten Handler. Reine statische Helferklasse, keine eigene Persistenz. Kollaborateure: `core_component`, `MoodleQuickForm`, die konkreten `conditions\*`-Handler (via `certificate_conditions_interface`).

## Methoden

### `public static function add_conditions_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public static
- **Zweck:** Baut den Condition-Typ-Selektor und haengt die Felder des aktuell gewaehlten Handlers ein. **Seiteneffekte:** registriert NoSubmit-Button `btn_certificateconditiontype`, fuegt Select + (unsichtbaren) Submit-Button hinzu, setzt Default, mutiert `$ajaxformdata['certificateconditiontype']` (per Referenz), ruft `add_logic_to_mform` des gewaehlten Handlers. **Bewertung:** A — robustes Fallback: bei ungueltigem `selectedlogictype` faellt der Code auf den Default `'0'` zurueck (Zeilen 66-69); der `'0' => choose...`-Eintrag deckt sich mit dem Default-Key, das Dropdown bleibt konsistent.

### `public static function get_conditions()` — public static
- **Zweck:** Liefert je eine frische Instanz aller Handler im `conditions`-Namespace. **Seiteneffekte:** `core_component::get_component_classes_in_namespace(...)`, instanziiert jede Klasse. **Rueckgabe:** Array von Handler-Objekten. **Bewertung:** A — Standard-Discovery; instanziiert pro Aufruf neu (kein Caching, aber Mengen sind klein).

### `public static function get_condition(string $name)` — public static
- **Zweck:** Liefert eine Handler-Instanz anhand des Kurz-Klassennamens. **Seiteneffekte:** prueft `class_exists`, instanziiert ggf. **Rueckgabe:** Handler-Objekt oder `null`. **Bewertung:** A — der FQCN wird per String-Konkatenation gebaut; da `class_exists` vorab prueft, kein Risiko (keine User-getriebene Klasseninstanziierung ausserhalb des Namespaces moeglich).

## Bewertungs-Resümee
Schlanke, korrekte Discovery-/Selector-Schicht mit sauberem Default-Fallback und konsistentem `'0'`-Sentinel im Dropdown. Keine funktionalen Maengel. Klassen-Score **A / -**.
