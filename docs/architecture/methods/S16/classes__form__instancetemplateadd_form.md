# instancetemplateadd_form — Methoden-Doku
**Datei:** `classes/form/instancetemplateadd_form.php` · **LOC:** 57 · **Subsystem:** S16 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`instancetemplateadd_form` ist eine minimale `moodleform` mit genau einem Eingabefeld: dem Namen, unter dem die aktuelle Booking-Instanz als Vorlage (Instance-Template) gespeichert werden soll. Keine eigene Persistenz, keine Validierungs-Override — reine Eingabemaske. Kollaborateur: `$CFG` (fuer die `formatstringstriptags`-Abhaengige Typwahl).

## Methoden

### `public function definition()` — public
- **Zweck:** Definiert das Formular: Header, Textfeld `name` (size 128) mit Pflichtfeld-Regel und Action-Buttons. **Seiteneffekte:** liest `$CFG->formatstringstriptags`, um den Feldtyp zwischen `PARAM_TEXT` (Tags strippen) und `PARAM_CLEANHTML` zu waehlen. **Bewertung:** A — korrekt und idiomatisch; die `formatstringstriptags`-Abfrage spiegelt das Core-Verhalten fuer Namensfelder.

## Bewertungs-Resümee
So einfach wie es geht: ein Pflicht-Textfeld mit korrekter typabhaengiger Sanitisierung. Keine Schwaechen. Klassen-Score **A / P3**.
