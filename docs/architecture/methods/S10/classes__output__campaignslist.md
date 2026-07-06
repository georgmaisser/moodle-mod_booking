# campaignslist — Methoden-Doku
**Datei:** `classes/output/campaignslist.php` · **LOC:** 175 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`campaignslist` ist ein Renderable/Templatable-DTO fuer die Admin-Liste der Buchungskampagnen. Der Konstruktor erhaelt eine Liste von Kampagnen-Records (typischerweise aus `booking_campaigns`), reichert jeden Record je nach `type` (Customfield- bzw. Blockbooking-Kampagne) mit lokalisierten Anzeigedaten an (Typname, Start/Ende, Beschreibung der angewendeten Bedingungen) und legt die Records als Arrays in `$this->campaigns` ab. Keine eigene Persistenz; reine View-Aufbereitung. Kollaborateure: `get_string`, `current_language`, `json_decode` (Kampagnen-JSON), die Mustache-Templates der Kampagnen-Seite. Score-Hinweis aus CLASS_INDEX: DTO, B.

## Methoden

### `public function __construct(array $campaigns)` — public
- **Zweck:** Iteriert ueber die uebergebenen Kampagnen-Records und reichert je nach `$campaign->type` (`MOD_BOOKING_CAMPAIGN_TYPE_CUSTOMFIELD` / `MOD_BOOKING_CAMPAIGN_TYPE_BLOCKBOOKING`) Anzeigefelder an: `bookingcampaigntype`, dekodiertes `json`, `description` (via `render_description`), `localizedtype`, `localizedstart`/`localizedend` (via `render_localized_timestamp`). Jeder Record wird per `(array)`-Cast in `$this->campaigns` abgelegt.
- **Seiteneffekte:** Mutiert die uebergebenen Record-Objekte (Anreicherung per Referenz), `json_decode`, `get_string`, `current_language`. Kein DB-/Cache-Zugriff.
- **Bewertung:** B — Zwei `case`-Bloecke sind nahezu identisch (nur der `localizedtype`-String und `bookingcampaigntype` unterscheiden sich → Duplikation). Das lokal aufgebaute `$a` (Z.56-58 / 67-69) mit `bofieldname`/`fieldvalue` wird nirgends weiterverwendet (toter Code). Records ohne passenden `type` landen unangereichert (ohne `description`/`localized*`) in der Liste — vom Template tolerierbar, aber implizit.

### `private function render_localized_timestamp(int $timestamp, string $lang = 'en'): string` — private
- **Zweck:** Formatiert einen Unix-Timestamp sprachabhaengig (`de` → `d. M Y, H:i`, sonst englisches `M d Y, H:i`).
- **Seiteneffekte:** Keine (reines `date()`).
- **Rueckgabe:** Formatierter Datums-String.
- **Bewertung:** B — Verwendet `date()` mit Server-Zeitzone statt Moodle-`userdate()`; die Monatsnamen sind nicht ueber Moodle lokalisiert (PHP-locale-abhaengig), und das `break` nach jedem `return` ist toter Code. Funktional unkritisch.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Liefert `['campaigns' => $this->campaigns]` fuer das Mustache-Template.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** Array mit Kampagnenliste.
- **Bewertung:** A — trivialer Pass-through.

### `private function render_description(object $campaignobj): string` — private
- **Zweck:** Baut aus dem dekodierten Kampagnen-JSON einen menschenlesbaren Bedingungstext: optional ein Buchungsoptions-Feld-Teil (`bofieldname` + Operator `=`/`!~` + `fieldvalue`) und ein Custom-Profile-Field-Teil (`cpfield` + Operator `=`/`~`/`!~` + `cpvalue`), per UND-String verknuepft.
- **Seiteneffekte:** `get_string` (mehrere Sprachkeys). Keine DB-/Cache-Zugriffe.
- **Rueckgabe:** Beschreibungstext; `""`, wenn keiner der beiden Teile gesetzt ist.
- **Bewertung:** B — Verwendet Assignment-in-`empty()` (`!empty($a->bofieldname = ...)`) als kompakten Idiom; nicht abgedeckte Operatorwerte fallen still durch (`campaignfieldnameoperator`-`switch` hat keinen `default`, der Operator-String bleibt dann unbelegt). Lesbarkeit leidet, Funktion korrekt.

## Bewertungs-Resümee
Reines View-DTO ohne Persistenz. Schwaechen: Duplikation der beiden Kampagnentyp-`case`-Bloecke, ungenutzte `$a`-Objekte (toter Code), nicht-Moodle-lokalisierte `date()`-Formatierung mit Server-Zeitzone und still durchfallende Operator-`switch`es. Alles kosmetisch/funktional unkritisch. Klassen-Score **B / P3**.
