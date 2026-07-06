# actions_info — Methoden-Doku
**Datei:** `classes/booking_rules/actions_info.php` · **LOC:** 149 · **Subsystem:** S06 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`actions_info` ist die Discovery-/Form-Service-Klasse fuer Booking-Rule-Actions. Sie kennt keine Instanz-State (rein statisch), entdeckt alle Action-Implementierungen per `glob` im Verzeichnis `classes/booking_rules/actions/` und baut den Action-Teil des Rule-Formulars (Select + bedingt eingeblendete Action-Felder). Persistenz: keine. Kollaborateure: `MoodleQuickForm`, die `booking_rule_action`-Implementierungen (jede liefert Name, Kompatibilitaet und Form-Felder), `$CFG->dirroot`.

## Methoden

### `public static function add_actions_to_mform(MoodleQuickForm &$mform, array &$repeateloptions, ?array &$ajaxformdata = null)` — public static
- **Zweck:** Rendert das Action-Auswahl-Dropdown (`bookingruleactiontype`) plus den NoSubmit-Trigger-Button, gefiltert auf die im aktuellen `$ajaxformdata`-Kontext kompatiblen Actions, und haengt anschliessend die Form-Felder der ausgewaehlten Action an. Ohne `ajaxformdata` wird nur die erste Action gerendert (Initial-Render). **Seiteneffekte:** mutiert `$mform` (registerNoSubmitButton, addElement select/submit, setDefault/setType) und `$repeateloptions` (via `add_action_to_mform` der Action); `get_string`-Lookups. **Rueckgabe:** void. **Bewertung:** C — Funktioniert, aber mehrere Fragilitaeten: (1) das Matching der gewaehlten Action (Z.89-92) vergleicht den lokalisierten `get_name_of_action()`-String gegen `get_string(str_replace("_","",$type), 'mod_booking')` — ein sprach-/string-abhaengiger Vergleich statt Klassennamen-Identitaet, der bei Uebersetzungs- oder String-Key-Drift bricht. (2) `array_reverse($actionsforselect)` ordnet die Optionen nur ueber die (alphabetische) glob-Reihenfolge, also kosmetisch undefiniert. (3) Im else-Zweig wird per `break` nur die erste Action gerendert — abhaengig von der glob-Sortierung, nicht von einer fachlichen Default-Wahl.

### `public static function get_actions()` — public static
- **Zweck:** Entdeckt und instanziiert alle Action-Klassen im actions-Verzeichnis (Dateiname == Klassen-Kurzname). **Seiteneffekte:** `glob($CFG->dirroot.'/mod/booking/classes/booking_rules/actions/*.php')`, `class_exists` + `new $filename()` je Datei. **Rueckgabe:** Array von `booking_rule_action`-Instanzen. **Bewertung:** B — bewaehrtes Plugin-Discovery-Pattern. Kosten: bei jedem Form-Render werden saemtliche Action-Objekte neu instanziiert (Dateisystem-glob + Reflektion); leichtgewichtig, aber kein Caching. Setzt voraus, dass jede Action einen argumentlosen Konstruktor hat.

### `public static function get_action(string $actionname)` — public static
- **Zweck:** Faktory fuer eine einzelne Action ueber ihren Klassen-Kurznamen. **Seiteneffekte:** `class_exists` + `new $filename()`. **Rueckgabe:** `booking_rule_action`-Instanz oder `null`, wenn die Klasse nicht existiert. **Bewertung:** A — kompakt und defensiv (null statt Fatal bei unbekanntem Namen). `$actionname` stammt aus gespeicherter Rule-Config; der zusammengesetzte FQCN ist auf den Namespace fixiert, der `class_exists`-Guard verhindert beliebige Instanziierung.

## Bewertungs-Resümee
Solide, zustandslose Discovery-/Form-Service-Klasse. Hauptschwaeche ist das string-basierte Action-Matching in `add_actions_to_mform` (lokalisierter Name als Schluessel statt Klassen-Identitaet) und die von der glob-Reihenfolge abhaengige Default-/Sortier-Logik — beides funktioniert heute, ist aber wartungsfragil. Keine Daten- oder Sicherheitsrisiken. Klassen-Score **B / P2**.
