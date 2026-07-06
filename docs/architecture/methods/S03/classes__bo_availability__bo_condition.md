# bo_condition — Methoden-Doku
**Datei:** `classes/bo_availability/bo_condition.php` · **LOC:** 184 · **Subsystem:** S03 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`bo_condition` ist das zentrale **Interface**, das den vollstaendigen Vertrag fuer eine einzelne Verfuegbarkeits-/Buchungsbedingung einer Buchungsoption definiert. Jede konkrete Condition (z.B. `alreadyreserved`, `booking_time`, `bookitbutton`) implementiert dieses Interface. Trotz des PHPDoc-Wortlauts „Base class" handelt es sich um ein reines `interface` ohne Implementierung oder Persistenz. Es legt das Doppel-Gate-Modell fest: `is_available()` baut die Pre-Booking-Modals und steuert die Sichtbarkeit, `hard_block()` ist die letzte Sperre direkt vor der Buchung (nur geprueft, wenn `is_available()` bereits false liefert). Kollaborateure: `booking_option_settings` (zu pruefendes Item), `MoodleQuickForm` (Form-Integration), Konsument ist `bo_info` (orchestriert die Condition-Kette). Keine Properties, keine Konstanten.

## Methoden

### `public function is_json_compatible(): bool` — public
- **Zweck:** Unterscheidet anpassbare/JSON-serialisierbare Conditions von hartkodierten. **Seiteneffekte:** keine (Vertrag). **Rueckgabe:** true bei JSON-faehiger Condition. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Gibt an, ob die Condition Form-Elemente im Options-mform anzeigt. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not): bool` — public
- **Zweck:** Kernpruefung der Verfuegbarkeit fuer ein Item/User; `$not` invertiert die Logik und traegt laut PHPDoc stets den „realen" NOT-Wert (relevant fuer verschachtelte NOT-AND/NOT-OR-Gruppen, damit die Nutzeranzeige sinnvoll bleibt). **Seiteneffekte:** keine (Vertrag); Implementierungen sollen Course/modinfo ueber `$info`-Getter beziehen. **Rueckgabe:** true wenn verfuegbar. **Bewertung:** A — gut dokumentierter, sorgfaeltig motivierter Vertrag.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Komplementaere Hart-Sperre kurz vor der Buchung; verhindert ungewollte Buchung selbst dann, wenn `is_available()` (fuer Modal-/Prepage-Zwecke) false lieferte. Nur ausgewertet, wenn `is_available()` false ist. **Seiteneffekte:** keine. **Rueckgabe:** true = blockt hart. **Bewertung:** A — die Semantik (Subbookings immer false, unbeantwortete Policy true) ist klar beschrieben.

### `public function get_description(booking_option_settings $settings, $userid, $full, $not)` — public
- **Zweck:** Liefert beschreibenden Text zur Restriktion; `$full` trennt Staff-Sicht (alle Infos) von Studenten-Sicht (nur unerfuellte Bedingungen). **Seiteneffekte:** keine. **Rueckgabe:** untypisiert (Implementierungen geben i.d.R. ein Array `[isavailable, description, prepageflag, buttonflag]` zurueck). **Bewertung:** B — fehlender Rueckgabetyp im Vertrag (Implementierungen liefern Arrays, der Name suggeriert String); leichte Vertragsunschaerfe.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid)` — public
- **Zweck:** Fuegt die Form-Elemente der Condition zum Options-mform hinzu. **Seiteneffekte:** mutiert das per Referenz uebergebene `$mform`. **Rueckgabe:** void. **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Rendert eine optionale Zusatzseite vor dem Buchungsprozess (z.B. Booking-Policy). **Seiteneffekte:** keine im Vertrag. **Rueckgabe:** Array. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Liefert Template-Name + Daten zum Rendern des Action-Buttons (z.B. Preis-/BookIt-Button). **Seiteneffekte:** keine im Vertrag. **Rueckgabe:** `[templatename, data]`. **Bewertung:** A.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert zusaetzliches SQL, um Optionen nicht nur zu blocken, sondern in Listen ganz auszublenden; `$params` wird per Referenz mit Query-Parametern befuellt. **Seiteneffekte:** mutiert `$params`. **Rueckgabe:** SQL-Fragment-Array. **Bewertung:** B — Vertrag spezifiziert die genaue Array-Form (Implementierungen liefern 5-Tupel wie `['', '', '', [], '']`) nicht; ohne Lektuere konkreter Implementierungen mehrdeutig.

### `public function get_id(): int` — public
- **Zweck:** Liefert die (i.d.R. hartkodierte) Condition-ID. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Liefert den lokalisierten Anzeigenamen. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Gibt an, ob die Condition uebersprungen werden darf (Admin-konfigurierbares Skip/Freeze). **Seiteneffekte:** keine. **Bewertung:** A.

## Bewertungs-Resümee
Sauberer, ausfuehrlich dokumentierter Interface-Vertrag, der das Herzstueck des Availability-Subsystems definiert. Einzige Schwaechen sind fehlende Rueckgabetypen bei `get_description()` und die unspezifizierte Array-Form von `return_sql()`/`render_page()`/`get_description()`, die nur durch Konvention statt durch Signatur abgesichert ist. Funktional unkritisch. Klassen-Score **A / P3**.
