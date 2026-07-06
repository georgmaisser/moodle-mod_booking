# bookings — Methoden-Doku
**Datei:** `classes/external/bookings.php` · **LOC:** 303 · **Subsystem:** S11 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`bookings` ist eine Read-Webservice-Funktion (`external_api`), die fuer eine `courseid` ein tief verschachteltes Aggregat liefert: alle Booking-Instanzen des Kurses mit Metadaten, Booking-Manager, Dateianhaengen, Kategorien, sowie je Option Texte/Zeiten, optional Teilnehmerliste, Lehrkraefte und Sessions. Primaer fuer externe/Mobile-Konsumenten (Gate `showinapi`). Keine Schreibvorgaenge. Kollaborateure: `$DB` (booking, user, booking_category), `singleton_service` (booking-by-cmid, option_settings, booking_answers, user), `external_util`/`external_files`, `file_rewrite_pluginfile_urls`, `apply_tags`.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert `courseid`, `printusers`, `days` — alle PARAM_TEXT mit VALUE_DEFAULT ''. **Seiteneffekte:** keine. **Bewertung:** B — `courseid`/`days` als PARAM_TEXT statt PARAM_INT ist locker typisiert (semantisch ganzzahlig).

### `public static function execute($courseid = '0', $printusers = '0', $days = '0'): array` — public static
- **Zweck:** Liest alle `booking`-Records des Kurses und baut je sichtbarer/berechtigter Instanz mit `showinapi=1` das verschachtelte Antwort-Aggregat. **Seiteneffekte:** `require_once locallib.php`; `validate_parameters`; `$DB->get_records('booking', ['course' => $courseid])`; pro Instanz `get_coursemodule_from_instance`, `context_module::instance`, `has_capability`, `singleton_service::get_instance_of_booking_by_cmid`, `apply_tags`, `file_rewrite_pluginfile_urls`, `$DB->get_record('user', ...)` (Manager); pro Option `get_instance_of_booking_option_settings`, optional `get_instance_of_booking_answers` + pro User `get_instance_of_user`. **Rueckgabe:** verschachteltes Array. **Bewertung:** C — mehrere echte Schwaechen:
  - **Sichtbarkeits-Gate (Z.103):** `strcmp($cm->visible, "1") == 0 || has_capability('mod/booking:bookforothers', $context)`. Die ODER-Logik gibt bei sichtbarem Modul Daten ohne jede Capability frei; nur bei verstecktem Modul wird die Capability verlangt. Kein `require_login`/`validate_context` im gesamten Pfad.
  - **N+1 ueber Optionen/User (Z.166–209):** pro Option ein `option_settings`-Lookup, bei `printusers` pro Teilnehmer ein `get_instance_of_user`-Singleton, pro Option `booking_answers` — skaliert mit Optionen×Usern.
  - **Manager-Lookup (Z.119/130):** `$DB->get_record('user', ['username' => ...])` kann `false` liefern; `$manager->id ?? 0` loest dann eine „read property on bool"-Warnung aus (`??` faengt nur Undefined, nicht den Bool-Zugriff).
  - **`if ($printusers)` (Z.184):** truthy-Test auf PARAM_TEXT; der Default '0' ist zwar falsy, ein uebergebenes '0' ebenso — semantisch korrekt, aber implizit.
  - Positiv: Kategorien werden seit Fix (Z.149) per `get_records_list` gebuendelt geladen (kein N+1 mehr).

### `public static function execute_returns(): external_multiple_structure` — public static
- **Zweck:** Beschreibt die tiefe Rueckgabestruktur (Instanz → categories/options → users/teachers/sessions). **Seiteneffekte:** keine. **Bewertung:** B — sehr umfangreich aber korrekt; einige Feld-Beschreibungstexte sind copy-paste-falsch (z.B. lastname „First", `imgurl` „Image url5").

## Bewertungs-Resümee
Funktional liefert der Service das gewuenschte Aggregat, aber das Sichtbarkeits-Gate gibt potenziell personenbezogene Teilnehmerdaten (E-Mail, Name) allein gegen Modul-Sichtbarkeit frei, ohne `require_login`/Kontext-Validierung, und der Aufbau ist N+1-lastig ueber Optionen und User. Plus latente Bool-Property-Warnung beim Manager-Lookup. Klassen-Score **C / P2**.
