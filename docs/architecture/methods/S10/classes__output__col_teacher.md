# col_teacher — Methoden-Doku
**Datei:** `classes/output/col_teacher.php` · **LOC:** 95 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`col_teacher` ist ein Renderable/Templatable-DTO fuer die Lehrkraefte-Spalte einer Buchungsoption. Es baut aus `booking_option_settings->teachers` eine Anzeige-Liste, optional mit Link auf die Teacher-Seite und optional mit Profilbild. Keine Persistenz; Kollaborateure: `moodle_url`, `core_user::get_user`, `user_picture`, globaler `$PAGE`, Config `booking/teacherslinkonteacher`.

## Methoden

### `public function __construct(int $optionid, booking_option_settings $settings, bool $loadprofileimage = false)` — public
- **Zweck:** Iteriert ueber `$settings->teachers`. Wenn `teacherslinkonteacher` gesetzt ist, wird je Teacher eine `teacherurl` auf `/mod/booking/teacher.php?teacherid=...` gesetzt. Bei `$loadprofileimage` wird der User per `core_user::get_user` geladen und bei vorhandenem `picture` ueber `user_picture` (Groesse 150) eine Bild-URL gebaut, sonst `image=false`. Die Teacher-Description wird via `format_text(...,$descriptionformat)` gerendert. Jeder Teacher wird als Array in `$this->teachers` abgelegt.
- **Seiteneffekte:** `get_config('booking','teacherslinkonteacher')`; bei `$loadprofileimage` pro Teacher `core_user::get_user($teacher->userid)` (ungecachter DB-Lookup) und `user_picture::get_url($PAGE)`; mutiert die Teacher-Objekte aus `$settings` direkt (setzt `teacherurl`/`image`/`description`).
- **Bewertung:** B — funktional korrekt. Zwei Anmerkungen: (1) `core_user::get_user` pro Teacher und gerenderter Zeile ist ein potentieller N+1 bei aktiviertem Profilbild (P3, nur wenn `$loadprofileimage`), waehrend an anderer Stelle `singleton_service::get_instance_of_user` gecacht waere; (2) das In-Place-Mutieren der `$settings->teachers`-Objekte (insbesondere `format_text` auf `description`) veraendert das ggf. gecachte Settings-Objekt — bei wiederholtem Rendern koennte die Beschreibung doppelt durch `format_text` laufen.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt `['teachers' => $this->teachers]` fuer das Mustache-Template zurueck.
- **Seiteneffekte:** keine.
- **Rueckgabe:** Array mit der Teacher-Liste.
- **Bewertung:** A — trivialer Passthrough.

## Bewertungs-Resümee
Sauberes Spalten-DTO mit optionaler Bild-/Link-Anreicherung. Schwaechen: ungecachter `core_user::get_user`-Lookup pro Teacher im Bild-Pfad und das In-Place-Mutieren potenziell gecachter Settings-Teacher-Objekte (Risiko mehrfachen `format_text`). Beide niederprior und kontextabhaengig. Klassen-Score **B / P3**.
