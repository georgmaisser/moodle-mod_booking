# option_schema_definition — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/option_schema_definition.php` · **LOC:** 530 · **Subsystem:** S15 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S15_wizard_options_skills.md)

## Klassenueberblick
Zustandslose Schema-Definitionsklasse fuer den Agent-Layer (`@package bookingextension_agent`). Liefert ueber eine einzige statische Methode den gemeinsamen Eigenschafts-Katalog (Property-Schema) fuer die create/update/bulk-update-Tasks von Buchungsoptionen. Reine deklarative Datenquelle: jeder Eintrag beschreibt ein Feld mit `type`, `description` und `required`-Flag, die als Tool-/Funktions-Schema an das LLM bzw. die Mutations-Tasks weitergereicht werden. Kollaborateure sind die konsumierenden Option-Mutation-Tasks (create/update/bulk-update Schemas), die diesen Block in ihr eigenes Schema mergen.

## Methoden

### `public static common_properties(): array`
- **Zweck:** Gibt den geteilten Property-Katalog fuer create/update/bulk-update-Schemas zurueck (Sessions/Datumsfelder, Slotbooking-Parameter, Selflearning, Verfuegbarkeitsbedingungen wie enrolledincourse/cohort/competency/userprofile, customform, prices, headerimage_token usw.).
- **Parameter:** keine.
- **Rueckgabe:** `array` — assoziatives Map `feldname => ['type' => string, 'description' => string, 'required' => bool]`. Rein statisch, identisch bei jedem Aufruf.
- **Seiteneffekte:** keine. Kein DB-Zugriff, kein Cache, keine Events, keine Globals — reines Literal-Return.
- **Aufrufkette:** Von wo: konsumiert durch die Option-Mutation-Task-Schemas (create/update/bulk-update) im Agent-Subplugin, die `common_properties()` in ihr Feldschema einbinden. Ruft selbst nichts auf.
- **Bewertung:** **C** — Funktional korrekt und nebenwirkungsfrei, aber ein ~497-LOC-Array-Literal in genau einer Methode (`option_schema_definition.php:32-529`). Laenge >80 LOC (Smell: Long Method / God-Data-Block). Da es sich um eine rein deklarative, flache Konfiguration ohne Logik/Verzweigung handelt, ist das Risiko gering; dennoch erschwert die Monolith-Struktur Wartung und Diff-Lesbarkeit. Verbesserung: thematische Aufteilung (z. B. `slot_properties()`, `availability_properties()`, `customform_properties()`) und Zusammenfuehrung in `common_properties()`. Inhaltliche Duplikation der Operator-/Override-Bloecke (enrolledincourse/cohort/competency/userprofile sind nahezu strukturgleich) liesse sich ueber einen Generator reduzieren.
