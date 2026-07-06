# bookingopeningtime — Methoden-Doku
**Datei:** `classes/option/fields/bookingopeningtime.php` · **LOC:** 244 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`bookingopeningtime` ist der Spiegel-Handler zu `bookingclosingtime`: gleicher Aufbau, gleiche Verzahnung mit der `booking_time`-Bedingung, nur fuer den Buchungs-OEffnungs-Zeitpunkt (ab wann gebucht werden darf). Kein Instanzzustand; statische Hooks. Persistenz: Spalten `bookingopeningtime`, `restrictanswerperiodopening`, `sqlfilter` und das serialisierte `availability`-JSON von `booking_options`. Kollaborateure: `booking_time`, `dates`, `field_base`, `fields_info`. Konfig: `$header = AVAILABILITY`, `$incompatiblefields = [EASY_BOOKINGOPENINGTIME]`. `instance_form_definition` ist leer (UI von der Bedingung).

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Berechnet den persistenten Opening-Wert (ggf. relativ zu coursestart/-end), spiegelt Opening-Felder nach `$formdata`, schreibt die Bedingung ins `availability`-JSON und setzt `sqlfilter`. **Seiteneffekte:** Spiegelt `coursestarttime`/`courseendtime`; ruft `booking_time::resolve_persistence_data` und `::upsert_condition_in_availability` (mutiert `$formdata->availability`); mutiert `$newoption->bookingopeningtime`, `$formdata->restrictanswerperiodopening`, `$newoption->sqlfilter`. **Rueckgabe:** Changes-Array; bei deaktiviertem `restrictanswerperiodopening` ohne Alt-Wert frueher Return `[]`, sonst `newvalue = 0`. **Bewertung:** C — identische Verflechtungs-Komplexitaet wie Closing-Variante; korrekt. `sqlfilter` wird von beiden Feldern (Opening + Closing) geschrieben — die `else if (!isset(...))`-Logik ist auf gemeinsame Reihenfolge angewiesen.

### `public static function validation(array $data, array $files, array &$errors): array` — public static
- **Zweck:** Validiert den relativen Opening-Modus (mode 2): braucht mindestens ein Optionsdatum, nicht zulaessig fuer Self-Learning-Kurse. **Seiteneffekte:** Schreibt ggf. `$errors['booking_time_opening_mode']`; ruft `dates::get_list_of_submitted_dates`, `booking_time::resolve_persistence_data`. **Rueckgabe:** `$errors`. **Bewertung:** C — wie bei Closing: die berechnete `$openingtime` (Z.177/179) ist tote Variable; die im Kommentar angekuendigte Grenzwertpruefung fehlt (siehe Findings).

### `public static function instance_form_definition(...)` — public static
- **Zweck:** Leerer Hook (UI via `booking_time`-Bedingung). **Seiteneffekte:** Keine. **Bewertung:** A.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Vorbelegung von `bookingopeningtime`, `restrictanswerperiodopening` und `bo_cond_booking_time_sqlfiltercheck`. Import-Pfad parst Strings via `strtotime($value)`. **Seiteneffekte:** Mutiert die drei genannten `$data`-Felder. **Bewertung:** B — Inkonsistenz zur Closing-Variante: hier `strtotime($data->{$key})` ohne Basis-Zeit, dort `strtotime($value, time())` mit `time()`-Basis. Bei absoluten Datumsangaben gleichwertig, bei relativen Strings divergierend. `$settings->sqlfilter` im Else-Zweig ohne `?? 0`-Guard.

### Triviale Properties
Sechs statische Konfig-Properties (Z.47–85). `use core_course_external;` (Z.27) ist ein ungenutzter Import.

## Bewertungs-Resümee
Zwilling von `bookingclosingtime` mit denselben Eigenschaften und derselben Schwaeche: `validation()` berechnet `$openingtime` ohne sie zu nutzen, die annoncierte Grenzwertpruefung fehlt (P3). Zusaetzlich kleine `strtotime`-Inkonsistenz gegenueber der Closing-Variante. Klassen-Score **C / P3**.
