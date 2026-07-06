# campaigns_info — Methoden-Doku
**Datei:** `classes/booking_campaigns/campaigns_info.php` · **LOC:** 570 · **Subsystem:** S07 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S07_booking_campaigns.md)

## Klassenueberblick
Statische Fassade/Registry fuer das Campaign-Subsystem von mod_booking. Verantwortet (1) Discovery aller Campaign-Typ-Klassen via `core_component`, (2) Instanziierung per Typ/Name, (3) CRUD auf der Tabelle `booking_campaigns` inkl. Cache-Purges, (4) Bau gemeinsamer mform-Felder (Custom-Field- und User-Profile-Bedingungen) und (5) Laufzeit-Auswertung, ob eine Kampagne aktiv ist / auf einen User zutrifft. Kollaborateure: `booking_campaign`-Subklassen unter `campaigns\`, `singleton_service` (Campaign-Cache), `booking_handler` (Customfields), `campaignslist`-Output, MoodleQuickForm. Reine Static-God-Klasse mit gemischter Verantwortung (Form-UI + DB + Matching-Logik).

## Methoden

### `add_campaigns_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null): void` — public static
- **Zweck:** Baut den Kampagnentyp-Auswahl-Block (Select + versteckter NoSubmit-Button) und delegiert dann an die typ-spezifische `add_campaign_to_mform()`.
- **Parameter/Rueckgabe:** mform per Referenz, optionale AJAX-Formdaten; void (mutiert mform).
- **Seiteneffekte:** Keine DB; ruft `get_string` mehrfach; instanziiert Campaign-Klassen via `get_campaigns`/`get_campaign_by_name`.
- **Aufrufkette:** Aus Campaign-Edit-DynamicForm; ruft `self::get_campaigns`, `self::get_campaign_by_name`, `$campaign->add_campaign_to_mform`.
- **Bewertung:** B — ~55 LOC, klar strukturiert; leichte Mischung Reflection+UI, aber akzeptabel.

### `get_campaigns(): array` — public static
- **Zweck:** Liefert je eine frische Instanz aller Klassen im Namespace `booking_campaigns\campaigns`.
- **Rueckgabe:** Array von `booking_campaign`-Instanzen.
- **Seiteneffekte:** `core_component::get_component_classes_in_namespace` (Autoload-Scan), `new` pro Klasse.
- **Aufrufkette:** von `add_campaigns_to_mform`.
- **Bewertung:** A — knapp, idiomatisch.

### `get_campaign_by_type(int $campaigntype)` — public static
- **Zweck:** Mappt eine Typ-Konstante (CUSTOMFIELD/BLOCKBOOKING) auf den Klassennamen und instanziiert.
- **Rueckgabe:** Campaign-Instanz oder null.
- **Seiteneffekte:** `class_exists`, `new`.
- **Aufrufkette:** von `set_data_for_form`, `get_all_campaigns`.
- **Bewertung:** B — switch mit Magic-String-Mapping (parallel zu `get_campaign_by_name`), aber kurz und klar.

### `get_campaign_by_name(string $campaignname)` — public static
- **Zweck:** Instanziiert Campaign-Klasse direkt ueber den Kurz-Klassennamen.
- **Rueckgabe:** Instanz oder null.
- **Seiteneffekte:** `class_exists`, `new` (unvalidierter Klassenname aus Formdaten → an Namespace gebunden, daher begrenzt).
- **Aufrufkette:** von `add_campaigns_to_mform`, `save_booking_campaign`.
- **Bewertung:** B — ok; Name aus User-Input wird in Klassennamen konkateniert (durch festen Namespace-Prefix entschaerft).

### `set_data_for_form(object &$data): object` — public static
- **Zweck:** Laedt bei vorhandener id den Kampagnen-Record und laesst die Typ-Klasse die Formular-Defaults setzen.
- **Rueckgabe:** angereichertes Datenobjekt (oder leeres stdClass).
- **Seiteneffekte:** DB-Read `booking_campaigns`; `$campaign->set_defaults`.
- **Aufrufkette:** aus Campaign-Edit-Form; ruft `get_campaign_by_type`.
- **Bewertung:** B — kurz; kein Null-Guard falls `get_campaign_by_type` null liefert (potenzieller Fatal), aber Datenkonsistenz vorausgesetzt.

### `save_booking_campaign(stdClass &$data): void` — public static
- **Zweck:** Delegiert Persistenz an die Typ-Klasse und purged danach drei Caches.
- **Seiteneffekte:** `$campaign->save_campaign` (DB-Write), 3x `cache_helper::purge_by_event` (setbackoptionstable/-settings/-prices).
- **Aufrufkette:** aus Campaign-Form-Submit; ruft `get_campaign_by_name`.
- **Bewertung:** B — ok; Purge-Trio dupliziert sich mit `delete_campaign`.

### `delete_campaign(int $campaignid): void` — public static
- **Zweck:** Loescht einen Kampagnen-Record, resettet Singleton-Cache und purged Caches.
- **Seiteneffekte:** DB-Delete `booking_campaigns`; `singleton_service::reset_campaigns`; 3x `cache_helper::purge_by_event`.
- **Aufrufkette:** aus Campaign-Verwaltung (Loesch-Aktion).
- **Bewertung:** B — `global $DB` ok; Purge-Trio dupliziert (siehe save).

### `return_rendered_list_of_saved_campaigns(): string` — public static
- **Zweck:** Rendert die Liste gespeicherter Kampagnen via Renderer/`campaignslist`.
- **Seiteneffekte:** `$PAGE->get_renderer`, Template-Render.
- **Aufrufkette:** aus Settings-/Verwaltungsseite; ruft `get_list_of_saved_campaigns`.
- **Bewertung:** A — schlanke Render-Fassade.

### `get_list_of_saved_campaigns(): array` — private static
- **Zweck:** Holt alle Kampagnen-Records aus dem Singleton-Cache.
- **Seiteneffekte:** `singleton_service::get_all_campaigns` (cachet DB-Read). `global $DB` deklariert aber ungenutzt.
- **Aufrufkette:** von `return_rendered_list_of_saved_campaigns`, `get_all_campaigns`.
- **Bewertung:** B — toter `global $DB`; sonst trivial.

### `delete_all_campaigns(): bool` — public static
- **Zweck:** Loescht alle Kampagnen-Records und zerstoert den Singleton-Cache.
- **Seiteneffekte:** DB-Delete gesamte Tabelle `booking_campaigns`; `singleton_service::destroy_all_campaigns`. Kein Cache-Purge (anders als delete_campaign).
- **Aufrufkette:** Test-/Cleanup-Pfade.
- **Bewertung:** B — ungenutzter `global $DB` wuerde nur teilweise gelten (DB hier benutzt); fehlende Cache-Purges inkonsistent zu delete_campaign.

### `get_all_campaigns(): array` — public static
- **Zweck:** Liefert alle Kampagnen als instanziierte, mit Record befuellte Campaign-Objekte.
- **Seiteneffekte:** indirekt DB via `get_list_of_saved_campaigns`; `set_campaigndata` pro Objekt.
- **Aufrufkette:** Laufzeit-Matching (z.B. Preis/Verfuegbarkeit), ruft `get_campaign_by_type`.
- **Bewertung:** B — ok; kein Null-Guard bei unbekanntem `$record->type`.

### `add_customfields_to_form(MoodleQuickForm &$mform, ?array &$ajaxformdata = null): void` — public static
- **Zweck:** Baut den grossen gemeinsamen Bedingungs-Block: Name, Booking-Customfield (Name/Operator/Wert) und optional User-Profilfeld (Feld/Operator/Wert), inkl. dynamischer Wertelisten per SQL.
- **Seiteneffekte:** DB-Reads `user_info_field`; 2 inline-SQLs ueber `customfield_*` bzw. `user_info_data`/`user_info_field` (`get_fieldset_sql`); `booking_handler::get_customfields`; viele mform-Mutationen.
- **Aufrufkette:** aus `campaign_*::add_campaign_to_mform`.
- **Bewertung:** D — ~185 LOC (classes/booking_campaigns/campaigns_info.php:282), zwei handgebaute SQL-Statements im Form-Builder (gemischte Verantwortung UI+DB+Query), `$params['bofieldname']` ohne vorherige Initialisierung (Notice-Risiko, classes/booking_campaigns/campaigns_info.php:349), doppelte Element-Id `warning` (Zeile 289 und 386). Hauptkandidat fuer Refactoring.

### `check_if_profilefield_applies(array $fields, string $fieldname, string $operator, int $userid = 0): bool` — public static
- **Zweck:** Prueft, ob fuer einen User ein Profilfeld einer der Bedingungen genuegt.
- **Seiteneffekte:** `singleton_service::get_instance_of_user`; liest `$USER`.
- **Aufrufkette:** Laufzeit-Campaign-Matching.
- **Bewertung:** D — Bug: `$userid = $userid ?? $USER->id` greift nie, da Default `0` nicht null ist → bei nicht uebergebener userid wird User 0 geladen statt aktuellem User (classes/booking_campaigns/campaigns_info.php:487). Zudem Zuweisungen-in-Bedingung (`$blocking = ...`, Zeilen 497/502/508) als verwirrender Stil; `!~`-Zweig liefert frueh `false` statt das Negativ-Ergebnis ueber alle Felder zu sammeln (inkonsistente Operator-Semantik). Smell + echter Logikfehler.

### `check_if_campaign_is_active(int $starttime, int $endtime, $fieldname, string $fieldvalue, string $operator): bool` — public static
- **Zweck:** Entscheidet anhand Zeitfenster + Feldwert/Operator, ob eine Kampagne aktuell aktiv ist.
- **Rueckgabe:** bool.
- **Seiteneffekte:** keine (nur `time()`).
- **Aufrufkette:** Laufzeit-Campaign-Auswertung.
- **Bewertung:** C — verschachtelte if/else-Operator-Faelle mit subtiler Semantik (classes/booking_campaigns/campaigns_info.php:539-567), schwer testbar/lesbar; reine Logik aber fragil bei Operator-Erweiterung.

## Triviale Akzessoren
Keine reinen Getter/Setter in dieser Klasse (alle Methoden statisch und logiktragend).
