# purge_campaign_caches — Methoden-Doku
**Datei:** `classes/task/purge_campaign_caches.php` · **LOC:** 159 · **Subsystem:** S13 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S13_tasks.md)

## Klassenueberblick
`purge_campaign_caches` ist ein `\core\task\adhoc_task`, der zum Kampagnen-Start und -Ende die relevanten Booking-Caches leert. Wird im Custom-Data eine `campaignid` mitgegeben, prueft der Task zusaetzlich fuer alle Optionen mit begrenzter Platzzahl (`maxanswers > 0`), ob der Kampagnen-Uebergang Plaetze freigegeben hat (z.B. weil eine Kampagne die `maxanswers` per `limitfactor` reduziert hatte und nun wieder aufhebt), und triggert in dem Fall `sync_waiting_list` sowie das Event `bookingoption_freetobookagain`. Persistenz: nur lesend auf `booking_options`; schreibende Wirkung indirekt ueber `sync_waiting_list`/`check_if_free_to_book_again`. Kollaborateure: `cache_helper` (Event-Purges), `singleton_service`, `booking_answers`, `booking_option`, Config `skipsetbackoptionstable`. Custom-Data: `campaignid`, `limitfactor`, `campaignstart`.

## Methoden

### `public function get_name()` — public
- **Zweck:** Sichtbarer Task-Name. **Seiteneffekte:** `get_string('taskpurgecampaigncaches', 'mod_booking')`. **Rueckgabe:** lokalisierter String. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Purged die Kampagnen-relevanten Caches und prueft optional, ob der Kampagnen-Uebergang bei limitierten Optionen Plaetze freigegeben hat. **Seiteneffekte:** `cache_helper::purge_by_event('setbackoptionstable')` (gated auf `!skipsetbackoptionstable`), `purge_by_event('setbackoptionsettings')`, `purge_by_event('setbackprices')`; bei gesetzter `campaignid`: `singleton_service::destroy_all_campaigns()`, `$DB->get_fieldset_select('booking_options', 'id', 'maxanswers > 0')`, pro Option ein weiterer `$DB->get_field(...)` und Singleton-Aufloesung, ggf. `sync_waiting_list(false, true)` + `booking_option::check_if_free_to_book_again(...)`; Singleton-Cleanup je Option; viele `mtrace`-Ausgaben. **Bewertung:** C:
  - **N+1 auf `maxanswers` (Z.95 vs. Z.107):** `get_fieldset_select(..., 'maxanswers > 0')` liefert nur die IDs; anschliessend wird in der Schleife per `$DB->get_field('booking_options', 'maxanswers', ['id' => $optionid])` der Wert *einzeln* nachgeladen — eine Query pro Option. Da das Filterkriterium ohnehin `maxanswers` ist, koennte ein `get_records_select(..., 'id, maxanswers')` beide Informationen in einem Roundtrip liefern. Bei vielen limitierten Optionen messbarer N+1. **(P2)**
  - **Per-Option-Schwergewicht:** Pro Option werden Option-Settings- und Answers-Singletons aufgeloest, Plaetze gezaehlt und ggf. die Warteliste synchronisiert. Der Cleanup (`destroy_booking_option_singleton`/`destroy_booking_answers`) begrenzt den Speicher korrekt, der DB-/CPU-Aufwand skaliert aber linear mit der Gesamtzahl limitierter Optionen *unabhaengig* davon, ob sie zur Kampagne gehoeren — es gibt keinen Filter auf die konkrete `campaignid`. **(P3)**
  - **`before`-Heuristik (Z.126–132):** Die Rekonstruktion der `maxanswers` vor dem Uebergang via `ceil(dbmaxanswers * limitfactor)` setzt voraus, dass die uebergebene `limitfactor` exakt der zuvor aktiven Kampagnen-Modifikation entspricht; bei ueberlappenden Kampagnen oder geaenderter Reihenfolge kann `wasfullybooked` falsch berechnet werden (zu viel/zu wenig `freetobookagain`-Events).
  - **Robuster Fehler-Umgang:** Jede Option ist in `try/catch` gekapselt (Z.105/152) → ein Fehler bei einer Option bricht den Gesamtlauf nicht ab.
  - **Akteur im cron:** `check_if_free_to_book_again($settings, 0, true)` uebergibt bewusst Userid `0` (kein eingeloggter User im Cron) — korrekt dokumentiert.

## Bewertungs-Resümee
Funktional korrekter Kampagnen-Cache-Purge mit sinnvoll gekapselter Platz-Freigabe-Erkennung und gutem Speicher-/Fehler-Management. Abzuege fuer den vermeidbaren N+1 beim Nachladen von `maxanswers`, das fehlende Filtern auf die konkrete Kampagne (Voll-Scan aller limitierten Optionen) und die annahmebehaftete `limitfactor`-Rueckrechnung. Klassen-Score **C / P2**.
