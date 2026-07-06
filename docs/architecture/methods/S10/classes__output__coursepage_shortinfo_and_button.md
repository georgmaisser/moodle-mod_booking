# coursepage_shortinfo_and_button — Methoden-Doku
**Datei:** `classes/output/coursepage_shortinfo_and_button.php` · **LOC:** 111 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`coursepage_shortinfo_and_button` ist ein reines Render-DTO (`renderable, templatable`), das die Kurzinfo-plus-Buchen-Button-Box einer Booking-Instanz auf der Kursseite mit Template-Daten versorgt. Es haelt keine eigene Persistenz, sondern liest seine Werte beim Konstruktor aus den `booking->settings` (via `singleton_service`) sowie aus den Globals `$COURSE`/`$CFG`. Kollaborateure: `singleton_service::get_instance_of_booking_by_cmid`, das zugehoerige Mustache-Template und `view.php` als Linkziel.

## Methoden

### `public function __construct($cm)` — public
- **Zweck:** Sammelt aus dem Course-Module-Objekt, den Booking-Settings und den Globals die fuer das Template noetigen Werte (Kursname, Eventtyp, Button-URL auf `view.php?...&whichview=showall`, Kurzinfo). **Seiteneffekte:** liest `$COURSE`/`$CFG`; ruft `singleton_service::get_instance_of_booking_by_cmid((int)$cm->id)` (kann DB/Cache anstossen). **Bewertung:** B — kompakt und unkritisch; `$cmid` wird als „numeric" deklariert aber direkt aus `$cm->id` uebernommen, `$COURSE->fullname` setzt voraus, dass die Instanz auf der globalen Kursseite gerendert wird (impliziter Kontext).

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Reicht die vier Anzeigewerte (`coursename`, `eventtype`, `buttonurl`, `shortinfo`) als flaches Array an Mustache durch. **Seiteneffekte:** keine. **Rueckgabe:** `array` mit den vier Template-Keys. **Bewertung:** A — triviale Passthrough-Methode; `$output` ungenutzt (Interface-Vorgabe).

### Triviale Properties
Sechs oeffentliche Properties (`booking`, `cmid`, `coursename`, `eventtype`, `buttonurl`, `shortinfo`, Z.44–72) als Werte-Halter ohne Getter; im Konstruktor befuellt.

## Bewertungs-Resümee
Schlankes, gut nachvollziehbares Coursepage-DTO ohne Logik ueber Datenmontage hinaus. Einzige Schwaeche ist die implizite Abhaengigkeit von `$COURSE` (Kursname) statt einer expliziten Uebergabe. Funktional unkritisch. Klassen-Score **B / P3**.
