# csvcolumn — Methoden-Doku
**Datei:** `classes/import/csvcolumn.php` · **LOC:** 175 · **Subsystem:** S18 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S18_import_export.md)

## Klassenueberblick
`csvcolumn` ist das DTO einer einzelnen importierbaren CSV-Spalte fuer den Booking-CSV-Import. Es buendelt Metadaten einer Spalte: `columnname`, `localizedname`, `mandatory`, `unique`, `format`, `type`, `importinstruction` sowie zwei freie Slots `defaultvalue` und `transform` (eine optionale Callable). Persistenz: keine (reines Werte-/Konfigobjekt, in Import-Definitionen instanziiert). Kollaborateure: CSV-Import-Pfade (`mod_booking\import\*`), die solche Spaltendefinitionen aufbauen und auswerten. Die enthaltenen Konvertierungsmethoden `date_to_string`/`string_to_date` sind unfertige Stubs.

## Methoden

### `public function __construct($columnname = '', $localizedname = '', $mandatory = true, $unique = false, $type = 'default', $format = PARAM_TEXT, $defaultvalue = null, $transform = null, $importinstruction = '')` — public
- **Zweck:** Initialisiert alle Spalten-Properties aus den Konstruktorargumenten. **Seiteneffekte:** ruft am Ende `$this->apply('pluginname')` auf. **Rueckgabe:** —. **Bewertung:** C — der abschliessende `apply('pluginname')`-Aufruf verwirft seinen Rueckgabewert und bewirkt nur etwas, wenn eine `transform`-Closure gesetzt ist; sein Zweck (warum ausgerechnet der Literal-String `'pluginname'`?) ist unklar und wirkt wie Test-/Debug-Rest. Default-Mismatch: der Parameter heisst `$format` mit Doc `@param string $format`, der Default `PARAM_TEXT` (Z.99) wird aber dem `$this->format`-Property zugewiesen, dessen Property-Default `'string'` ist — die Quelle der Wahrheit fuer das Format-Token ist damit uneinheitlich.

### `public function apply($value)` — public
- **Zweck:** Wendet die optionale `transform`-Callable auf einen Wert an. **Seiteneffekte:** ruft die in `$this->transform` hinterlegte Closure auf. **Rueckgabe:** Ergebnis der Closure, oder `void`/null wenn keine Transform gesetzt ist. **Bewertung:** C — inkonsistente Rueckgabe (bei leerer Transform `return;` → null, sonst der Closure-Wert); der einzige interne Aufruf im Konstruktor ignoriert das Ergebnis, sodass die Methode dort wirkungslos ist (sofern die Closure keine Seiteneffekte hat). Kein Typ-Check auf Callable vor Aufruf.

### `public function set_property($param, $value)` — public
- **Zweck:** Setzt eine Property generisch ueber ihren Namen, sofern sie bereits existiert. **Seiteneffekte:** mutiert `$this->$param`. **Rueckgabe:** bool — true bei Erfolg, false wenn Property nicht gesetzt. **Bewertung:** C — Existenzpruefung via `isset($this->$param)` statt `property_exists`: nicht-initialisierte/null Properties (`defaultvalue`, `transform` sind ohne Default deklariert) gelten als „nicht vorhanden", sodass `set_property('defaultvalue', ...)` oder `set_property('transform', ...)` faelschlich false zurueckgibt und nichts setzt. Siehe Findings.

### `public function date_to_string($date, $format)` — public
- **Zweck:** Soll ein Datumsobjekt in einen String des definierten Formats wandeln. **Seiteneffekte:** keine. **Rueckgabe:** aktuell immer der Leerstring `""`. **Bewertung:** D — unimplementierter Stub; ignoriert beide Parameter und liefert konstant `""`. Bei produktivem Einsatz Datenverlust.

### `public function string_to_date($date, $format)` — public
- **Zweck:** Soll einen String in ein Datumsobjekt des definierten Formats wandeln. **Seiteneffekte:** keine. **Rueckgabe:** aktuell immer der Leerstring `""`. **Bewertung:** D — unimplementierter Stub (Rueckgabe `""` statt eines Datums-/Objekttyps, widerspricht `@return mixed`-Intention). Bei produktivem Einsatz Datenverlust.

### Triviale Properties
Neun oeffentliche Properties (Z.36–78): `columnname`, `localizedname`, `mandatory`, `unique`, `format`, `type`, `importinstruction` mit Defaults sowie `defaultvalue` und `transform` ohne Default (uninitialisiert) — reine Werte-Halter ohne Getter.

## Bewertungs-Resümee
Im Kern ein einfaches Spalten-DTO, das aber durch unfertige Stubs (`date_to_string`/`string_to_date` liefern konstant `""`), die fragwuerdige `set_property`-Existenzpruefung (`isset` statt `property_exists`) und den raetselhaften `apply('pluginname')`-Aufruf im Konstruktor belastet ist. Solange die Stubs und der date-Pfad nicht genutzt werden, bleibt es harmlos; werden sie aktiviert, drohen stille Leer-Werte. Klassen-Score **C / P3**.
