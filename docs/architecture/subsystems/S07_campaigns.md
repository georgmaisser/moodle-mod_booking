# S07 — campaigns

## Zweck & Grenzen

Das Subsystem `booking_campaigns` realisiert zeit- und regelbasierte Kampagnen, die auf
Buchungsoptionen wirken: Es modifiziert zur Laufzeit (a) den **Preis** (Preisfaktor,
optional userspezifisch via Custom-User-Profile-Feld) und (b) die **Verfügbarkeit /
Buchungsgrenze** (`maxanswers`-Limitfaktor) bzw. **blockiert** das Buchen ganz, wenn ein
Auslastungsschwellwert über-/unterschritten ist.

Eine Kampagne gilt für eine Buchungsoption, wenn sie zeitlich aktiv ist (`starttime`/`endtime`)
**und** ein Booking-Option-Customfield-Kriterium (`bofieldname`/`fieldvalue`/Operator)
zutrifft. Optional kann zusätzlich ein User-Profilfeld-Kriterium (`cpfield`/`cpvalue`/
`cpoperator`) die Wirkung pro Nutzer einschränken (userspezifischer Preis, userspezifische
Blockade).

Grenzen des Subsystems:
- Es **definiert** Kampagnen und ihre Wirkungslogik (active? / price / limit / blocking),
  **persistiert** sie und liefert das mform-Formular für die Pflege.
- Es **wendet** sie aber nicht selbst an: Die Anwendungspunkte liegen außerhalb
  (`booking_option_settings::set_settings_data`, `price::apply_campaigns`,
  `booking_option::is_blocked_by_campaign`). Cache-Übergänge an Start/Ende werden von der
  Adhoc-Task `mod_booking\task\purge_campaign_caches` außerhalb dieses Verzeichnisses
  ausgeführt.

## Position im Gesamtsystem

```
Form (classes/form/campaignsform.php, deletecampaignform.php)
        │ save / delete
        ▼
campaigns_info  ──get_campaign_by_type/by_name──▶  campaign_customfield / campaign_blockbooking
        │  (Factory, Persistenz-Fassade, gemeinsame mform-Teile, statische Helfer)
        │  purge_by_event(...)         set_campaigndata / save_campaign
        ▼
DB-Tabelle {booking_campaigns}     singleton_service (Campaign-Cache: get_all_campaigns,
                                    reset_campaigns, destroy_all_campaigns)

Anwendung (außerhalb des Scopes):
  booking_option_settings::set_settings_data  ──campaign_is_active → apply_logic──▶ maxanswers / settings->campaigns[]
  price::apply_campaigns                        ──campaign_is_active → get_campaign_price──▶ Preis
  booking_option::is_blocked_by_campaign        ──is_blocking──▶ Buchung blockiert
  task\purge_campaign_caches (Start/Ende)       ──purge_by_event + freetobookagain-Check
```

`campaigns_info::get_campaigns()` entdeckt alle Kampagnen-Typen dynamisch über
`core_component::get_component_classes_in_namespace("mod_booking", 'booking_campaigns\campaigns')`
— ein Subplugin-/Erweiterungspunkt: neue Klassen im Namespace werden automatisch aufgenommen.

## Schlüsselkonzepte

- **Interface `booking_campaign`** als Vertrag aller Kampagnentypen (12 Methoden:
  Form-Aufbau, Persistenz, Aktivitäts-/Preis-/Limit-/Blocking-Logik).
- **Zwei Kampagnentypen** (Konstanten `MOD_BOOKING_CAMPAIGN_TYPE_CUSTOMFIELD` und
  `..._BLOCKBOOKING`, definiert in `lib.php`):
  - `campaign_customfield`: ändert Preis (`pricefactor`) und Limit (`limitfactor`,
    `extendlimitforoverbooked`).
  - `campaign_blockbooking`: blockiert Buchung je nach Auslastung
    (`blockbelow`/`blockabove`/`blockalways`, `percentageavailableplaces`), zeigt
    `blockinglabel`.
- **Aktivitätsprüfung** zentral in `campaigns_info::check_if_campaign_is_active()`
  (Zeitfenster + Customfield-Operator `=`/`!~`).
- **Userspezifik** zentral in `campaigns_info::check_if_profilefield_applies()`
  (Operatoren `=`/`~`/`!~` auf User-Profilfeldern).
- **Persistenz hybrid**: feste Spalten in `{booking_campaigns}` + ein `json`-Blob für
  typabhängige Zusatzfelder.
- **Cache-Kohärenz**: Jede Mutation purged `setbackoptionstable`,
  `setbackoptionsettings`, `setbackprices`; zusätzlich werden Adhoc-Tasks zu
  `starttime`/`endtime` eingereiht, damit die Übergänge auch ohne Speichervorgang greifen.

## Datenfluss

**Speichern (Form):** `campaignsform` → `campaigns_info::save_booking_campaign()` →
`get_campaign_by_name()` → `campaign_*::save_campaign()` baut `$record` (feste Spalten +
`json_encode`) und insert/update auf `{booking_campaigns}`; reiht zwei
`purge_campaign_caches`-Adhoc-Tasks ein (bei Limit ≠ 1 mit `campaignid`/`limitfactor`-
Customdata für `freetobookagain`-Check). Anschließend `cache_helper::purge_by_event(...)`.

**Laden (Form-Defaults):** `campaigns_info::set_data_for_form()` lädt Record, ermittelt Typ,
ruft `campaign_*::set_defaults()` (dekodiert `json` zurück in Formfelder).

**Anwenden Limit/Verfügbarkeit:** `booking_option_settings::set_settings_data()` holt
`campaigns_info::get_all_campaigns()`, prüft je Kampagne `campaign_is_active()` und ruft
`apply_logic()`. `campaign_customfield::apply_logic()` setzt `maxanswers` neu (über
`get_campaign_limit()` inkl. Overbooking-Korrektur). `campaign_blockbooking::apply_logic()`
hängt sich selbst in `settings->campaigns[]` für späteres Blocking.

**Anwenden Preis:** `price::apply_campaigns()` ruft je aktiver Kampagne
`get_campaign_price($price, $userid)`; `campaign_customfield` multipliziert mit `pricefactor`
(userspezifisch nur falls `check_if_profilefield_applies` zutrifft) und rundet (Präzision
abh. von `local_shopping_cart`-Config `rounddiscounts`).

**Anwenden Blocking:** `booking_option::is_blocked_by_campaign()` iteriert
`settings->campaigns` und ruft `is_blocking()`; `campaign_blockbooking` vergleicht belegte
Plätze (`booking_answers::count_places`) gegen `maxanswers * percentageavailableplaces%`.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|-------|--------|-------|-----|----------|-------------|-----------------|
| classes/booking_campaigns/booking_campaign.php | `booking_campaign` (interface) | Vertrag/Extension-Point | 126 | 12 | A | P3 |
| classes/booking_campaigns/campaigns_info.php | `campaigns_info` | Factory + Persistenz-Fassade + gemeinsame mform-Teile + statische Aktiv-/Profil-Helfer | 570 | 12 | C | P2 |
| classes/booking_campaigns/campaigns/campaign_customfield.php | `campaign_customfield` | Kampagnentyp Preis/Limit | 438 | 13 | C | P2 |
| classes/booking_campaigns/campaigns/campaign_blockbooking.php | `campaign_blockbooking` | Kampagnentyp Blocking | 418 | 13 | C | P2 |

### `booking_campaign` (Interface) — booking_campaign.php

Vertrag aller Kampagnentypen. Klein, klar, gut dokumentiert. Kollaborateure im Vertrag:
`booking_option_settings`, `MoodleQuickForm`, `stdClass`.

Methoden-Inventar (alle `public`, Signaturen):
- `add_campaign_to_mform(MoodleQuickForm &$mform, array &$ajaxformdata)` — Formelemente des Typs anhängen.
- `get_name_of_campaign_type(bool $localized = true): string` — lokalisierter Typname.
- `save_campaign(stdClass &$data)` — Kampagne speichern.
- `set_defaults(stdClass &$data, stdClass $record)` — Formdefaults aus Record.
- `set_campaigndata(stdClass $record)` — Record (inkl. JSON) in Objekt laden.
- `campaign_is_active(int $optionid, booking_option_settings $settings): bool` — aktiv für Option?
- `get_campaign_price(float $price, int $userid = 0): float` — modifizierter Preis.
- `apply_logic(booking_option_settings &$settings, stdClass &$dbrecord)` — typ-spezifische Wirkung anwenden.
- `is_blocking(booking_option_settings $settings, int $userid): array` — Blockade-Status + Label.
- `get_name_of_campaign(): string`, `get_id_of_campaign(): int`, `user_specific_price(): bool` — triviale Akzessoren.

### `campaigns_info` — campaigns_info.php

Statische God-Klasse (alle 12 Methoden static): vereint Factory/Discovery, Persistenz-
Orchestrierung (save/delete/get), gemeinsamen Formularteil und zwei reine Regel-Helfer. Hohe
Verantwortungskonzentration; gemischte Abstraktionsebenen (SQL-Strings für Profilfeld- und
Customfield-Werte direkt in der Form-Methode). Kollaborateure: `core_component`,
`cache_helper`, `singleton_service`, `booking_handler` (Customfields), `campaignslist`/Renderer,
`$DB`, die Kampagnenklassen.

Methoden-Inventar:
- `add_campaigns_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null): void` `static` — Typ-Auswahl + delegiert an gewählten Kampagnentyp.
- `get_campaigns()` `static` — alle Kampagnen-Instanzen via `core_component`-Namespace-Scan (Discovery).
- `get_campaign_by_type(int $campaigntype)` `static` — Factory per Typkonstante (Switch auf `campaign_customfield`/`campaign_blockbooking`).
- `get_campaign_by_name(string $campaignname)` `static` — Factory per Klassen-Kurzname.
- `set_data_for_form(object &$data): object` `static` — Record laden, `set_defaults` delegieren.
- `save_booking_campaign(stdClass &$data): void` `static` — `save_campaign` delegieren + 3 Caches purgen.
- `delete_campaign(int $campaignid)` `static` — DB-Delete + `singleton_service::reset_campaigns` + Cache-Purge.
- `return_rendered_list_of_saved_campaigns()` `static` — HTML der Kampagnenliste via Renderer.
- `get_list_of_saved_campaigns(): array` `private static` — Records aus Singleton (`get_all_campaigns`).
- `delete_all_campaigns(): bool` `static` — alle Records + Singleton leeren.
- `get_all_campaigns(): array` `static` — alle Kampagnen instanziiert (`set_campaigndata` je Record).
- `add_customfields_to_form(MoodleQuickForm &$mform, ?array &$ajaxformdata = null): void` `static` — gemeinsamer Formteil (Name, Customfield-Auswahl+Wert via Ad-hoc-SQL, User-Profilfeld+Wert via Ad-hoc-SQL).
- `check_if_profilefield_applies(array $fields, string $fieldname, string $operator, int $userid = 0): bool` `static` — Profilfeld-Matching (`=`/`~`/`!~`).
- `check_if_campaign_is_active(int $starttime, int $endtime, $fieldname, string $fieldvalue, string $operator): bool` `static` — Zeitfenster + Customfield-Operatorlogik.

### `campaign_customfield` — campaigns/campaign_customfield.php

Kampagnentyp für Preis- und Limit-Modifikation. Öffentliche Felder als Daten-Container,
JSON-Hydrierung in `set_campaigndata`. `save_campaign` enthält zwei nahezu duplizierte
Persistenz-/Task-Zweige (Limit≠1 vs. =1). Kollaborateure: `campaigns_info`, `time_handler`,
`singleton_service` (booking_answers), `purge_campaign_caches`, `\core\task\manager`, `$DB`.

Methoden-Inventar:
- `set_campaigndata(stdClass $record)` `public` — feste Felder + JSON (`bofieldname`, `fieldvalue`, `cpfield`/`cpvalue`/`cpoperator`, setzt `userspecificprice`).
- `add_campaign_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` `public` — gemeinsamer Formteil + Start/Ende + `pricefactor`/`limitfactor`/`extendlimitforoverbooked`.
- `get_name_of_campaign_type(bool $localized = true): string` `public` — Typname.
- `save_campaign(stdClass &$data)` `public` — Record + JSON bauen, insert/update, zwei `purge_campaign_caches`-Adhoc-Tasks (bei Limit≠1 mit `campaignid`/`limitfactor`-Customdata).
- `set_defaults(stdClass &$data, stdClass $record)` `public` — Formdefaults aus Record/JSON.
- `campaign_is_active(int $optionid, booking_option_settings $settings): bool` `public` — delegiert an `campaigns_info::check_if_campaign_is_active`.
- `get_campaign_price(float $price, int $userid = 0): float` `public` — `pricefactor`-Multiplikation (userspezifisch über Profilfeldprüfung), Rundung via `local_shopping_cart`-Config.
- `get_campaign_limit(int $limit, booking_option_settings $settings): int` `private` — `limitfactor` auf `maxanswers`, Overbooking-Korrektur über vor `starttime` gebuchte User.
- `apply_logic(booking_option_settings &$settings, stdClass &$dbrecord)` `public` — setzt `maxanswers` neu.
- `is_blocking(booking_option_settings $settings, int $userid): array` `public` — No-op (`status=false`).
- `get_name_of_campaign()`, `get_id_of_campaign()`, `user_specific_price()` `public` — triviale Akzessoren.

### `campaign_blockbooking` — campaigns/campaign_blockbooking.php

Kampagnentyp für Buchungssperre nach Auslastung. Struktur eng an `campaign_customfield`
(Felder, `set_campaigndata`, `set_defaults`, Akzessoren weitgehend dupliziert). Blocking-Logik
in `is_blocking`. Kollaborateure: `campaigns_info`, `booking_answers`, `singleton_service`,
`booking_context_helper`, `time_handler`, `purge_campaign_caches`, `\core\task\manager`, `$DB`.

Methoden-Inventar:
- `set_campaigndata(stdClass $record)` `public` — feste Felder + JSON (zusätzlich `blockoperator`, `blockinglabel`, `hascapability`, `percentageavailableplaces`). Achtung: greift `$jsonobj->blockoperator/blockinglabel/hascapability` ohne `??`-Guard.
- `add_campaign_to_mform(...)` `public` — gemeinsamer Formteil + Start/Ende + `blockoperator`/`percentageavailableplaces`/`blockinglabel`.
- `get_name_of_campaign_type(bool $localized = true): string` `public` — Typname.
- `save_campaign(stdClass &$data)` `public` — Record + JSON, insert/update, zwei `purge_campaign_caches`-Tasks (ohne Limit-Customdata).
- `set_defaults(stdClass &$data, stdClass $record)` `public` — Formdefaults aus Record/JSON.
- `campaign_is_active(int $optionid, booking_option_settings $settings): bool` `public` — delegiert an `campaigns_info::check_if_campaign_is_active`.
- `get_campaign_price(float $price, int $userid = 0): float` `public` — gibt Preis unverändert zurück.
- `get_campaign_limit(int $limit): int` `private` — No-op (gibt Limit zurück; tote Hilfsmethode, ungenutzt).
- `apply_logic(booking_option_settings &$settings, stdClass &$dbrecord)` `public` — registriert sich in `settings->campaigns[]`/`dbrecord->campaigns[]`.
- `is_blocking(booking_option_settings $settings, int $userid): array` `public` — Auslastungsvergleich (`blockbelow`/`blockabove`/`blockalways`) + optionale userspezifische Profilfeldprüfung → `status`/`label`.
- `get_name_of_campaign()`, `get_id_of_campaign()`, `user_specific_price()` `public` — triviale Akzessoren.

## Persistenz

- **Tabelle `{booking_campaigns}`**: feste Spalten `id`, `type`, `name`, `starttime`,
  `endtime`, `pricefactor`, `limitfactor`, `extendlimitforoverbooked`, `json`. Typabhängige
  Felder liegen im `json`-Blob (`bofieldname`, `campaignfieldnameoperator`, `fieldvalue`,
  `cpfield`, `cpoperator`, `cpvalue`, sowie für Blockbooking `blockoperator`, `blockinglabel`,
  `hascapability`, `percentageavailableplaces`).
- **Lesende Ad-hoc-SQL** in `campaigns_info::add_customfields_to_form()` auf
  `{customfield_field}`/`{customfield_category}`/`{customfield_data}` (Customfield-Wert-
  Vorschläge) und `{user_info_data}`/`{user_info_field}` (Profilfeld-Werte) sowie
  `{user_info_field}` (Feldliste).
- **Caches**: Invalidierung über `cache_helper::purge_by_event('setbackoptionstable' |
  'setbackoptionsettings' | 'setbackprices')` bei jedem Save/Delete. In-Memory-Cache der
  Kampagnen-Records im `singleton_service` (`$campaigns`, mit `get_all_campaigns`/
  `reset_campaigns`/`destroy_all_campaigns`).
- **Zeitgesteuerte Cache-Übergänge**: `mod_booking\task\purge_campaign_caches` (außerhalb des
  Scopes) wird zu `starttime` und `endtime` eingereiht; bei Limit-Kampagnen mit
  `campaignid`/`limitfactor`, um nach Übergang `freetobookagain`-Events zu prüfen.

## Extension-Points

- **Interface `booking_campaign`** — Vertrag für neue Kampagnentypen.
- **Namespace-Discovery** `booking_campaigns\campaigns` via `core_component`
  (`campaigns_info::get_campaigns`): Neue Kampagnenklasse im Verzeichnis genügt zur Aufnahme in
  die Typ-Auswahl. ABER `get_campaign_by_type()` (campaigns_info.php:131) ist ein hartkodierter
  Switch auf zwei Typkonstanten — neue Typen brauchen hier zusätzlich einen Eintrag und eine
  `MOD_BOOKING_CAMPAIGN_TYPE_*`-Konstante in `lib.php`. Discovery und Factory sind also
  inkonsistent (halber Extension-Point).

## Bekannte Schulden (→ Blueprint)

- **Statische God-Klasse `campaigns_info`** (campaigns_info.php:48) mit gemischten
  Verantwortlichkeiten: Discovery/Factory, Persistenz, Form-Aufbau, Regel-Helfer in einem.
  `add_customfields_to_form()` (282–466) ist ~185 LOC mit zwei eingebetteten Ad-hoc-SQLs und
  UI-Aufbau in einem Block.
- **Inkonsistenter Extension-Point**: dynamische Discovery (campaigns_info.php:116) vs.
  hartkodierter Typ-Switch (campaigns_info.php:131–150) und `lib.php`-Konstanten.
- **Code-Duplizierung zwischen den beiden Kampagnentypen**: nahezu identische
  `set_campaigndata`-JSON-Hydrierung, `set_defaults`, Form-Header (Start/Ende), Akzessoren —
  keine gemeinsame abstrakte Basisklasse (campaign_customfield.php:97 ↔
  campaign_blockbooking.php:103 u. a.).
- **Doppelter Persistenzzweig** in `campaign_customfield::save_campaign()`
  (campaign_customfield.php:222–266): Insert/Update-Logik in beiden if/else-Ästen redundant.
- **Fragile JSON-Dekodierung** in `campaign_blockbooking::set_campaigndata()`
  (campaign_blockbooking.php:132–134): `$jsonobj->blockoperator/blockinglabel/hascapability`
  ohne `??`-Default → Notice/Fehler bei Altdatensätzen ohne diese Keys.
- **Tote Methode** `campaign_blockbooking::get_campaign_limit()` (campaign_blockbooking.php:319),
  No-op und ungenutzt.
- **Bug-verdächtige `!~`-Logik** in `check_if_profilefield_applies()`
  (campaigns_info.php:506–511): der `!~`-Zweig `return false` bei Nicht-Enthalten bricht die
  Schleife früh ab und ignoriert weitere Felder; Zuweisung-statt-Vergleich-Muster
  (`if ($blocking = ...)`) erschwert das Lesen.
- **Debug-abhängiger Kontrollfluss** beim Anwenden: in
  `booking_option_settings::set_settings_data()` (außerhalb Scope, ~684) werden Kampagnen-
  Exceptions nur bei `$CFG->debug` rethrown (zudem `=` statt `==` im Vergleich), sonst
  stillgeschluckt — Wirkung kann unbemerkt ausfallen.
- **Performance-TODOs** an der Aufrufstelle markiert
  (booking_option_settings.php:676–679: „This is a performance problem. We need to cache
  campaigns!"): `get_all_campaigns()` wird pro Option erneut instanziiert.
- **Keine dedizierten Unit-Tests** im Subsystem sichtbar (Kampagnenlogik nur indirekt über
  price/option-Tests); Aktiv-/Blocking-/Limit-Berechnung ist testkritisch.
