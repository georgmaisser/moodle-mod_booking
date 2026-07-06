# cmvisibility — Methoden-Doku
**Datei:** `classes/local/checkanswers/checks/cmvisibility.php` · **LOC:** 75 · **Subsystem:** S17 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`cmvisibility` ist einer der Check-Handler des `checkanswers`-Frameworks. Ein Check prueft pro Buchungsantwort, ob diese noch gueltig ist; `cmvisibility` prueft speziell, ob das Booking-Course-Module fuer den buchenden User noch sichtbar/zugaenglich ist. Wird via `core_component`-Namespace-Discovery vom Orchestrator gefunden und ueber `get_id()`/`$id` (= `checkanswers::CHECK_CM_VISIBILITY`) eingeordnet. Kollaborateure: `singleton_service` (Settings), `get_fast_modinfo` (CM-Sichtbarkeit pro User). Der Klassen-Doc-Kommentar („cartstore class") ist ein kopierter, irrefuehrender Header.

## Methoden

### `public static function get_id()` — public static
- **Zweck:** Liefert die Check-Kennung `self::$id`. **Seiteneffekte:** keine. **Rueckgabe:** int (`CHECK_CM_VISIBILITY`). **Bewertung:** A.

### `public static function check_answer(stdClass $answer)` — public static
- **Zweck:** Liefert `true`, solange der User auf das Booking-CM zugreifen kann; `false`, wenn der Zugriff blockiert ist (= Antwort gilt als ungueltig). **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` + `get_instance_of_booking_settings_by_cmid`; `get_fast_modinfo($bookingsettings->course, $answer->userid)->get_cm($settings->cmid)` (userspezifische modinfo). **Rueckgabe:** bool. Logik: `$access = !($cm->visible == "1" && !$cm->get_user_visible())` — blockierend ist genau der Fall „CM generell sichtbar, aber fuer diesen User nicht zugaenglich"; fehlt das CM ganz, wird `false` zurueckgegeben. **Bewertung:** A — die invertierte Blocking-Bedingung ist im Code kommentiert und korrekt. Kleiner Hinweis: ein vollstaendig auf `visible=0` gesetztes CM (fuer alle versteckt) liefert hier `true` (kein Block), weil die Bedingung explizit auf „generell sichtbar, aber userindividuell verborgen" zielt — bewusste, dokumentierte Semantik.

### Triviale Properties
`public static int $id = checkanswers::CHECK_CM_VISIBILITY;` (Z.43) als Discovery-Kennung.

## Bewertungs-Resümee
Schlanker, korrekter und sinnvoll kommentierter Sichtbarkeits-Check mit userspezifischer `get_fast_modinfo`-Abfrage. Einziger Makel ist der falsch kopierte Klassen-Header-Kommentar. Klassen-Score **A / P3**.
