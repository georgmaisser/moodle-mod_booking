# settings.php (prozedurales Admin-Settings-Skript) — Methoden-Doku

**Datei:** `mod/booking/settings.php` · **LOC:** 2788 · **Subsystem:** S22 · **Klassen-Score:** D / P2
> [Subsystem-Doc](../../subsystems/S22_settings.md)

## Klassenueberblick

Keine Klasse — prozedurales Moodle-Admin-Settings-Skript. Wird von Moodle beim Aufbau des
Admin-Settings-Baums inkludiert (`$ADMIN`, `$settings`). Baut die Plugin-Settings-Kategorie
`modbookingfolder` mit 8 externen Seiten (Templates, Preiskategorien, Semester, Customfields,
Verfuegbarkeitsbedingungen, Regeln, Zertifikate, Kampagnen) sowie — nur im `$ADMIN->fulltree` —
mehrere hundert `admin_setting_*`-Eintraege unter dem Namespace `booking/...`. Zentrale
Kollaborateure: `wb_payment` (PRO-Lizenzpruefung), `booking_handler` (Customfields),
`core_plugin_manager` (bookingextension-Settings), `price`, `checkanswers`, `placeholders_info`,
`htmlcomponents`. Es gibt KEINE benannten Funktionen; lediglich drei anonyme
`set_updatedcallback`-Closures und der grosse prozedurale Rumpf.

## Methoden

### Prozeduraler Rumpf (Skript-Top-Level, Z. 25–2788) — script-scope

- **Zweck:** Registriert Admin-Kategorie + externe Seiten (Z. 48–152) und befuellt im
  `$ADMIN->fulltree`-Zweig (Z. 154–2786) den Settings-Baum mit Lizenz-, Appearance-, General-,
  Teacher-, Cancellation-, Price-, iCal-, Signin-, Cache-, Mobile-, Shortcode- und
  Mailtemplate-Settings. Sehr viele Bloecke sind `if ($proversion) { … echte Settings … } else
  { … PRO-Hinweis-Heading … }`.
- **Parameter / Rueckgabe:** keine; arbeitet auf den von Moodle bereitgestellten Globals
  `$ADMIN`, `$settings`, `$module`, `$hassiteconfig`, `$CFG`, `$DB`. Setzt am Ende `$settings = null;`.
- **Seiteneffekte:**
  - **DB-Reads** waehrend Seitenaufbau: `booking_handler::get_customfields()` (mehrfach, Z. 202/326/554),
    `profile_get_custom_fields()`, `role_get_names()`/`get_roles_for_contextlevels()`,
    `$DB->get_records_sql("SELECT b.id, b.name FROM {booking} …")` (Z. 1181, Booking-Instanzen),
    `$DB->get_records_sql(… {tag}/{tag_instance} …)` (Z. 1582, Standard-Tags),
    `$DB->get_records('booking_options', ['bookingid'=>0])` (Z. 2154, Option-Templates),
    `booking_option::get_customfield_settings()` (Z. 2522), `price::get_possible_currencies()`,
    diverse `get_config('booking', …)` zur konditionalen Anzeige.
  - **Echo/Output** direkt: `echo $handler->check_for_forbidden_shortnames_and_return_warning();` (Z. 46) —
    Ausgabe waehrend Settings-Include.
  - **Lizenzentschluesselung:** `wb_payment::decryptlicensekey()` + `parse_license_content()` (Z. 225)
    zur Anzeige des farbigen Lizenzstatus (HTML inline gebaut, Z. 236–255).
  - **Plugin-Loop:** `core_plugin_manager::instance()->get_plugins_of_type('bookingextension')` mit
    dynamischer Klassen-Instanziierung `new "\\bookingextension_{$name}\\{$name}"()` und
    `$plugin->load_settings($ADMIN, 'modbookingfolder', $hassiteconfig)` (Z. 1340–1351; bewusst
    ausserhalb des PRO-Gates, eigene Lizenzlogik der Extensions).
  - **Cache:** keine direkten Purges hier (nur in den Closures, s. u.).
- **Aufrufkette:** von Moodle-Core (`admin/settings.php` / Settings-Tree-Build) inkludiert; ruft die
  o.g. Statik-/DB-/Lizenz-APIs.
- **Bewertung: D.** Idiomatisches, aber monolithisches Moodle-Settings-Skript. Smells:
  - Laenge ~2630 LOC im fulltree-Block, eine einzige prozedurale Verantwortung-Wolke —
    `settings.php:154` (God-Script).
  - Massive Struktur-Duplikation `if($proversion)/else PRO-Heading` ~25×, jeweils nahezu identisches
    `prolicensefeatures + profeatures:* + infotext:prolicensenecessary` — `settings.php:431`,
    `617`, `1258`, `1387`, `1462`, `1492`, `1547`, `1618`, `1677`, `2038`, `2258`, `2288`, `2320`,
    `2357`, `2591`, `2658` u. a.
  - Inline-SQL waehrend Seitenrender — `settings.php:1582` (Tag-JOIN) und `settings.php:1181`.
  - Mehrfacher identischer DB-Call `booking_handler::get_customfields()` ohne Memoisierung —
    `settings.php:202`, `326`, `554`.
  - Doppelt registrierte Settings-Keys: `booking/bookonlyondetailspage` zweimal hinzugefuegt
    (Z. 629–636 und erneut Z. 686–693) und `booking/shortcodesoff` zweimal (Z. 749–756 globaler
    General-Block und Z. 2643–2650 Shortcode-PRO-Block) — potenziell verwirrend/redundant
    (`settings.php:686`, `settings.php:2643`).
  - Wiederholte Heading-ID `'tabwhatsnew'` fuer voellig verschiedene else-Hinweise (Z. 1270, 1317,
    2661) — mehrdeutige Anchor-IDs.
  - HTML-String-Bau inline (Lizenztext Z. 236–255).

### `function () { … purge_by_event(...) }` — anonyme Closure (Z. 372) — callback (private scope)

- **Zweck:** updated-Callback fuer Setting `booking/showoptiondatesextrainfo`. Invalidiert nach
  Aenderung die abhaengigen wunderbyte_table-Caches.
- **Parameter / Rueckgabe:** keine / void.
- **Seiteneffekte:** `cache_helper::purge_by_event('setbackencodedtables')` und
  `cache_helper::purge_by_event('changesinwunderbytetable')` (Cache-Purge).
- **Aufrufkette:** von Moodle aufgerufen, wenn der Admin diesen Wert speichert.
- **Bewertung: A.** Trivial, korrekt zielgerichteter Purge.

### `function () { … purge_by_event(...) }` — anonyme Closure (Z. 1704) — callback (private scope)

- **Zweck:** identischer updated-Callback fuer `booking/waitinglistshowplaceonwaitinglist`
  (Spalten-Encoding aendert sich → Tabellencache leeren).
- **Seiteneffekte:** dieselben zwei `cache_helper::purge_by_event`-Aufrufe wie Z. 372.
- **Bewertung: B.** Korrekt, aber wortwoertliches Duplikat der Closure von Z. 372 — Kandidat fuer
  eine gemeinsame benannte Helper-Funktion (`settings.php:1704`).

### `function () { … checkanswers::create_bookinganswers_check_tasks(...) }` — anonyme Closure (Z. 1530) — callback (private scope)

- **Zweck:** updated-Callback fuer `booking/unenroluserswithoutaccess`. Stoesst beim Aktivieren
  systemweit Pruef-/Loesch-Tasks fuer alle betroffenen Booking-Answers an.
- **Parameter / Rueckgabe:** keine / void.
- **Seiteneffekte:** liest beide Sicherheits-Configs (`get_config('booking',
  'unenroluserswithoutaccessareyousure')` und `…/unenroluserswithoutaccess`); ruft bei beidseitig
  gesetztem Flag `checkanswers::create_bookinganswers_check_tasks(context_system::instance()->id,
  CHECK_ALL, ACTION_DELETE, 0)` → erzeugt systemweit Adhoc-Tasks (massive Nebenwirkung: kann
  Nutzer kursweit ausschreiben).
- **Aufrufkette:** von Moodle beim Speichern des Checkboxwerts; erfordert vorher aktivierte
  „Are you sure"-Checkbox (Z. 1521).
- **Bewertung: B.** Logik schlank und mit Double-Check abgesichert; Score nicht hoeher, weil die
  Nebenwirkung sehr folgenreich (systemweite Unenrol-Tasks) und nur durch ein zweites Config-Flag
  geschuetzt ist — bewusst riskanter Pfad (`settings.php:1530`).

### Triviale Akzessoren

Keine. Der Rest der Datei besteht ausschliesslich aus `$settings->add(new admin_setting_*())`-,
`$settings->hide_if()`- und `$ADMIN->add()`-Aufrufen ohne eigene Funktionsdefinitionen.

## Notizen

- Reines Konfigurations-/Bootstrap-Skript ohne Geschaeftslogik-Klasse; Refactoring-Nutzen vor allem
  durch Aufbrechen in Helfer (PRO-Heading-Factory, Customfield-Memoisierung) und Beseitigung der
  doppelten Setting-Keys.
- Potenzieller Bug-Verdacht (nicht verifiziert): `booking/bookonlyondetailspage` und
  `booking/shortcodesoff` werden je zweimal mit `$settings->add()` registriert — beim zweiten Add
  ueberschreibt Moodle i. d. R. den ersten Eintrag, was zu redundanter Pflege fuehrt.
