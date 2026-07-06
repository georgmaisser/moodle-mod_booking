# booking_option_preview_renderer — Methoden-Doku
**Datei:** `classes/local/wizard/booking_option_preview_renderer.php` · **LOC:** 94 · **Subsystem:** S15 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_ai.md)

## Klassenueberblick
`booking_option_preview_renderer` ist der serverseitige Preview-Renderer fuer Buchungsoptionen im Agenten-/Wizard-Kontext (S15). Er nimmt ein Preview-Payload (Liste von `optionids` und/oder einzelnes `optionid`), normalisiert es und rendert jede Option als Karten-Ansicht (`MOD_BOOKING_VIEW_PARAM_CARDS`) in deren EIGENER Booking-Instanz. Entscheidend (und im Code dokumentiert): die `cmid` wird aus den Option-Settings gezogen, nicht aus dem WS-Kontext des Agenten — eine Option kann zu einer anderen Booking-Instanz gehoeren, und ein falscher cmid wuerde eine leere „No records found"-Tabelle liefern. Zustandslos, keine Persistenz. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings`, `mod_booking\output\view`, Konstante `MOD_BOOKING_VIEW_PARAM_CARDS`.

## Methoden

### `public function render(array $payload, int $contextid, int $userid): string` — public
- **Zweck:** Erzeugt zusammengesetztes Preview-HTML fuer eine Menge von Optionsids. Normalisiert die ids (merge `optionids` + optionales `optionid`, `intval`, Filter `> 0`, `array_unique`, `array_values`), gibt bei leerer Menge `''` zurueck, und rendert je id ueber eine frische `view($optioncmid, 'showonlyone', $id)` und `get_rendered_showonlyone_table($id, CARDS)`. **Seiteneffekte:** pro Option `singleton_service::get_instance_of_booking_option_settings($id)` (Settings-Load, ggf. DB/Cache) und Aufbau eines `view`-Renderers; Exceptions je Option werden gefangen und als Bootstrap-`alert-danger`-Block emittiert. **Rueckgabe:** konkateniertes HTML (`<div class="booking-ai-preview-item ...">...</div>` je Treffer) oder `''`. **Bewertung:** A — robuste, defensive Normalisierung; pro Option isolierte `try/catch` verhindert, dass eine kaputte Option die ganze Preview killt; korrekte cmid-Aufloesung aus Settings (verhindert leere Cross-Instance-Tabellen). Schwachpunkt: im catch wird `$e->getMessage()` ungeescaped in HTML gegeben (kein `s()`/`format_string`) — bei nicht-kontrollierten Meldungen theoretischer Markup-/Info-Leak. Die Parameter `$contextid`/`$userid` werden nicht verwendet (Signatur-Vertrag des generischen Preview-Pfads).

## Bewertungs-Resümee
Sauber gebauter, zustandsloser Renderer mit gut begruendeter Per-Option-cmid-Logik und fehlertoleranter Schleife. Einzige Notiz: ungeescapter Exception-Text im Fehler-Alert (P3) und ungenutzte Kontext-Parameter. Funktional solide. Klassen-Score **A / P3**.
