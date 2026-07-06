# report_edit_bookingnotes — Methoden-Doku
**Datei:** `classes/output/report_edit_bookingnotes.php` · **LOC:** 87 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`report_edit_bookingnotes` ist ein schlankes Renderable/Templatable-DTO (`mod_booking\output`), das die Daten fuer das Inline-Editieren einer Buchungsnotiz im Report aufbereitet. Es haelt eine Notiz (`note`), die zugehoerige Booking-Answer-ID (`baid`), ein Editier-Flag (`editable`) und einen vorformatierten Anzeigewert (`displayvalue`). Keine eigene Persistenz — der aufrufende Report uebergibt bereits geladene Daten und das fertig ausgewertete Permission-Flag im Konstruktor-Array. Kollaborateure: `renderer_base`, das zugehoerige Mustache-Template; Permission-Check liegt beim Aufrufer.

## Methoden

### `public function __construct(array $data)` — public
- **Zweck:** Befuellt die Properties aus dem `$data`-Array (`note`, `baid`, `editable`). **Seiteneffekte:** keine externen; setzt `note` auf `" "` (Leerzeichen), falls leer — vermutlich um ein editierbares, aber visuell vorhandenes Inplace-Element zu erzwingen; `displayvalue` behaelt dagegen den (ggf. leeren) Originalwert. **Bewertung:** A — trivialer Werte-Mapper; der Leerzeichen-Default ist ein bewusster UI-Workaround, kein Fehler.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Liefert je nach Berechtigung entweder nur den Anzeigewert (read-only) oder die editierbaren Felder. **Seiteneffekte:** keine. **Rueckgabe:** bei `!editable` `['displayvalue' => (string)$this->displayvalue]`, sonst `['note' => ..., 'baid' => ...]`. **Bewertung:** A — saubere Trennung von Lese-/Editier-Ausgabe; der explizite `(string)`-Cast schuetzt gegen NULL-displayvalue im Template.

### Triviale Properties
`note`, `baid` (public) sowie `editable`, `displayvalue` (protected, Z.40–53) als Werte-Halter ohne Getter/Setter.

## Bewertungs-Resümee
Minimaler, klar lesbarer Output-DTO ohne Datenbankzugriff oder Logik jenseits eines Berechtigungs-Switches. Der Leerzeichen-Default fuer leere Notizen ist ein UI-Detail, kein Bug. Klassen-Score **A / P3**.
