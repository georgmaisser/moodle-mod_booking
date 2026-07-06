# teachers_instance_report — Methoden-Doku
**Datei:** `teachers_instance_report.php` · **LOC:** 246 · **Subsystem:** S21 · **Klassen-Score:** D / P2
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse) fuer den instanzweiten Lehrer-Report einer Buchungs-Instanz: Summe geleisteter Einheiten, Kurse, Fehlstunden und Vertretungen je Lehrer. Rendert `teachers_instance_report_table` mit Filter-Form (`teachers_instance_report_form`) und Excel/CSV-Download. Persistenz: nur lesend ueber `booking_teachers` × `user`; die abgeleiteten Kennzahlen (sum_units, units_courses, missinghours, substitutions) liefert die Tabellenklasse via Spalten-Callbacks. Subsystem-seitig als **D/P2** vorbewertet (inline-SQL, Display/Download dupliziert).

## Request-/Permission-Flow
1. **Parameter:** `cmid` (required PARAM_INT), `download` (optional PARAM_ALPHA).
2. **Auth:** `get_course_and_cm_from_cmid($cmid,'booking')` → `require_course_login($course,false,$cm)`; `$PAGE->activityheader->disable()`; Kontext `context_module::instance($cm->id)`.
3. **Capability-Gate:** Zugriff nur wenn `mod/booking:updatebooking` **oder** `mod/booking:addeditownoption` am Modul-Context — sonst Access-Denied-Seite + `die()`.
4. **cmid-Validierung:** `get_record_sql` joint `course_modules`×`modules` und prueft `m.name='booking'`; bei Miss Fehlerseite + `die()`.
5. **Instanz-Settings:** `singleton_service::get_instance_of_booking_settings_by_cmid($cmid)` → `$instancename`, `$bookingid` (Kommentar warnt explizit: bookingid ≠ cmid). Instanzname wird fuer den Dateinamen bereinigt (Spaces→`_`, Sonderzeichen entfernt, Mehrfach-`_` kollabiert, `format_string`).
6. **Tabellen-Setup:** `is_downloading()`, `define_baseurl` (page entfernt), nicht sortier-/kollabierbar, Download-Buttons oben. Unitlength aus Config, Fallback `'60'`.
7. **Filter-Form:** `teachers_instance_report_form` (vorbelegt mit cmid). Bei `get_data()`: `teacherid` (0 wenn leer) wird via `set_user_preference('teachersinstancereport_teacherid')` persistiert, damit auch der Download filtern kann.
8. **Zwei Ausgabe-Pfade** (Display vs. Download) mit je eigenen Header/Column-Definitionen und nahezu identischem SQL.

## Bewertung der Logik
- **SQL (beide Pfade):** `fields = "s.teacherid, s.firstname, s.lastname, s.email"`, `from` = Subselect `SELECT DISTINCT u.id teacherid, … FROM booking_teachers bt JOIN user u … WHERE bt.bookingid=:bookingid [AND u.id=:teacherid] ORDER BY u.lastname`. Der optionale `$andteacher`-Filter wird per String-Interpolation eingehaengt, der Wert aber als Bind-Param (`:teacherid`) gebunden — kein Injection-Risiko.
- **Quelle des Teacher-Filters:** Display-Pfad nutzt `$teacherid` aus dem Formular; Download-Pfad liest `get_user_preferences('teachersinstancereport_teacherid')`. Differenz zur Anzeige moeglich, wenn der Download ohne vorherige Filtersubmission ausgeloest wird (alte Preference).
- **Spaltenunterschied:** Anzeige zeigt `lastname` als Header-Spalte; Download stellt `lastname`+`firstname` getrennt voran. Kennzahlenspalten identisch.
- **Seiteneffekte:** HTML-/Datei-Ausgabe, `set_user_preference`.
- **Bewertung:** D — korrekt und parametrisiert, aber Display- und Download-Zweig duplizieren das vollstaendige SQL (nur Param-Quelle/Spaltenreihenfolge unterscheiden sich); der Filterzustand fuer den Download haengt an User-Preferences. Wartungslast hoch.

## Bewertungs-Resümee
Funktional sauber abgesicherter Instanz-Report (Modul-Context-Capability, cmid-Validierung, parametrisiertes SQL), aber klassisches Copy-Paste-SQL ueber zwei Pfade mit preference-getriebenem Download-Filter. Klassen-Score **D / P2**.
