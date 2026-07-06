# actions_info — Methoden-Doku
**Datei:** `classes/local/certificate_conditions/actions_info.php` · **LOC:** 143 · **Subsystem:** S19 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S19_certificates.md)

## Klassenueberblick
`actions_info` ist die Discovery-/Registry-Klasse der Action-Saeule des Zertifikatsbedingungs-Frameworks. Rein statisch, kein Zustand. Aufgaben: (1) per `core_component::get_component_classes_in_namespace` alle Action-Handler unter `mod_booking\local\certificate_conditions\actions` entdecken und instanziieren, (2) einen Form-Selector (`select` + versteckter NoSubmit-Button) fuer den Action-Typ aufbauen und die Felder der gewaehlten Action einhaengen, (3) einen Idempotenz-Check gegen bereits ausgestellte Zertifikate (`tool_certificate_issues`) bereitstellen. Kollaborateure: `core_component`, `MoodleQuickForm`, die `action_interface`-Implementierungen, `$DB` (Tabelle `tool_certificate_issues`). Konsumenten: das Zertifikatsbedingungs-Form und `actions\createcertificate::execute_action` (fuer den Idempotenz-Check). Der Datei-Header-DocBlock ist (wie in den Geschwister-Klassen) ein irrefuehrendes Copy-Paste.

## Methoden

### `public static function add_actions_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public static
- **Zweck:** Baut den Action-Typ-Selector. Iteriert ueber alle entdeckten Actions, mappt deren Kurz-Klassennamen auf den lokalisierten Anzeigenamen, registriert einen versteckten NoSubmit-Button (`btn_certificateactiontype`) fuer den Reload, ermittelt den aktuell gewaehlten Action-Typ (aus `ajaxformdata` oder Default = erste Action), normalisiert ungueltige Auswahlen zurueck auf den Default und delegiert dann an `add_action_to_mform` der gewaehlten Action. **Seiteneffekte:** mehrere `$mform`-Mutationen; schreibt die normalisierte Auswahl zurueck in `$ajaxformdata`. **Bewertung:** B — solides Standard-Pattern fuer dynamische Subform-Auswahl; `$actionsforselect` wird erst in der Schleife implizit initialisiert (undefiniert, falls keine Action existiert), wird aber nur als Select-Optionen verwendet.

### `public static function get_actions()` — public static
- **Zweck:** Entdeckt alle Action-Handler-Klassen im Actions-Namespace via `core_component` und gibt frisch instanziierte Objekte zurueck. **Seiteneffekte:** instanziiert jede gefundene Klasse (`new $classname()`). **Rueckgabe:** Array von Action-Instanzen. **Bewertung:** A — idiomatische Moodle-Discovery.

### `public static function get_action(string $name)` — public static
- **Zweck:** Liefert eine einzelne Action-Instanz anhand ihres Kurznamens; baut den FQCN zusammen und prueft `class_exists`. **Seiteneffekte:** ggf. `new $classname()`. **Rueckgabe:** Action-Instanz oder `null`. **Bewertung:** A.

### `public static function certificate_already_issued(int $conditionid, int $certid, int $userid)` — public static
- **Zweck:** Idempotenz-Pruefung: stellt fest, ob fuer (User, Template) bereits ein Zertifikat ausgestellt wurde, das zu genau dieser `conditionid` gehoert. Laedt alle `tool_certificate_issues` mit passendem `userid`+`templateid`, dekodiert deren `data`-JSON und vergleicht das eingebettete `conditionid`-Feld. **Seiteneffekte:** `$DB->get_records('tool_certificate_issues', …)`. **Rueckgabe:** `bool`. **Bewertung:** B — `json_decode` pro Record in einer Schleife; bei sehr vielen Issues desselben Templates pro User leichte Last (praktisch klein, da pro User/Template wenige Issues). Der `==`-Vergleich von `conditionid` ist lax-typisiert, hier unkritisch.

## Bewertungs-Resümee
Kompakte, idiomatische Discovery-/Registry-Klasse mit einem zusaetzlichen Idempotenz-Helfer. Der dynamische Selector ist ein Standardpattern, der Idempotenz-Check ist korrekt (matcht ueber das in den Issue-Daten persistierte `conditionid`). Schwaechen sind kosmetisch: Copy-Paste-Datei-DocBlock und das implizit initialisierte `$actionsforselect`. Keine funktionalen Bugs. Klassen-Score **B / P3**.
