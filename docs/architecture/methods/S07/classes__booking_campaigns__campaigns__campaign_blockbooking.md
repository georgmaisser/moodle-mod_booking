# campaign_blockbooking — Methoden-Doku
**Datei:** `classes/booking_campaigns/campaigns/campaign_blockbooking.php` · **LOC:** 418 · **Subsystem:** S07 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S07_*.md)

## Klassenueberblick
Implementiert das Interface `booking_campaign` fuer den Kampagnen-Typ "Blockbooking": Eine zeitlich begrenzte Kampagne, die Buchungsoptionen mit bestimmtem Custom-Field blockiert, sobald die Belegung unter/ueber einem Prozentsatz der maximalen Plaetze liegt (bzw. immer). Haelt einen flachen Zustand aus DB-Record + JSON-Blob und liefert via `is_blocking()` Blockier-Status und Label. Kollaborateure: `campaigns_info` (Form-Felder, Aktiv-Pruefung, Profilfeld-Match), `booking_answers`/`singleton_service` (Belegungszaehlung), `time_handler` (Form-Defaults), `purge_campaign_caches` (Adhoc-Task), `booking_context_helper`. Verantwortung ueberwiegend kohaerent; leichte Mischung aus Form-Building, Persistenz und Domaenenlogik in einer Klasse (typisch fuer Moodle-Subplugin-Strategieklassen).

## Methoden

### `set_campaigndata(stdClass $record): void` — public
- **Zweck:** Laedt DB-Record-Felder und den dekodierten JSON-Blob in die Objekt-Properties.
- **Parameter:** `$record` — Kampagnen-Record aus `booking_campaigns`. **Rueckgabe:** void.
- **Seiteneffekte:** Keine DB/Cache; nur Property-Zuweisungen. `json_decode` ohne Guard auf `$record->json`.
- **Aufrufkette:** Von `campaigns_info` beim Instanziieren einer Kampagne aus DB-Record gerufen.
- **Bewertung:** B — leichte Schachtelung bei cpfield/cpvalue-Normalisierung (array vs. string); `$jsonobj->blockoperator/blockinglabel/hascapability` (Z.132-134) ohne `?? ''`-Guard koennen bei aelteren/leeren JSONs Notices werfen (vgl. die mit `??` abgesicherten Felder darueber). Inkonsistente Defensive.

### `add_campaign_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null): void` — public
- **Zweck:** Baut die Formularfelder der Kampagne (Start/Endzeit, blockoperator, percentageavailableplaces, blockinglabel) inkl. Help-Buttons und hideIf.
- **Parameter:** `$mform` (Referenz), `$ajaxformdata` (Referenz). **Rueckgabe:** void.
- **Seiteneffekte:** `global $DB` deklariert aber ungenutzt (toter Import). Delegiert Customfields an `campaigns_info::add_customfields_to_form`. Keine DB-Writes.
- **Aufrufkette:** Von der Kampagnen-Edit-Form (campaigns_info/campaign-Form) gerufen.
- **Bewertung:** B — 50 LOC reiner deklarativer Form-Aufbau (akzeptabel); Smell: ungenutztes `global $DB` (campaign_blockbooking.php:146).

### `get_name_of_campaign_type(bool $localized = true): string` — public
- **Zweck:** Liefert lokalisierten oder rohen Typ-Namen der Kampagne.
- **Rueckgabe:** String. **Seiteneffekte:** `get_string` (i18n). **Bewertung:** A.

### `save_campaign(stdClass &$data): void` — public
- **Zweck:** Serialisiert Formulardaten in JSON, schreibt/aktualisiert den `booking_campaigns`-Record und queued zwei Adhoc-Cache-Purge-Tasks (zu Start- und Endzeit).
- **Parameter:** `$data` (Referenz, Formdaten). **Rueckgabe:** void.
- **Seiteneffekte:** DB-Write `booking_campaigns` (insert ODER update); setzt `$this->id` bei Insert; queued 2x `purge_campaign_caches` via `\core\task\manager::queue_adhoc_task` mit `set_next_run_time`.
- **Aufrufkette:** Von der Kampagnen-Speicher-Form gerufen.
- **Bewertung:** B — gemischte Verantwortung (JSON-Bau + Persistenz + Task-Queueing) in einer 44-LOC-Methode; statischer Task-Manager-Call. Tragbar fuer Moodle-Idiom.

### `set_defaults(stdClass &$data, stdClass $record): void` — public
- **Zweck:** Befuellt die Form-Defaults aus einem bestehenden Record (DB-Felder + JSON), per switch auf den Typ.
- **Seiteneffekte:** Keine; nur Zuweisungen. **Bewertung:** B — `switch` mit nur einem Case (BLOCKBOOKING) ist ueberdimensioniert; JSON-Zugriffe (Z.271-280) ohne `??`-Guard, koennen bei unvollstaendigem JSON Notices werfen.

### `campaign_is_active(int $optionid, booking_option_settings $settings): bool` — public
- **Zweck:** Prueft, ob die Kampagne fuer eine konkrete Option aktuell aktiv ist (Zeitfenster + Customfield-Match).
- **Seiteneffekte:** Mutiert `$this->fieldvalue` (array→scalar Normalisierung) als Nebeneffekt. Delegiert an `campaigns_info::check_if_campaign_is_active`.
- **Bewertung:** B — leichter Smell: lesendes-wirkendes Pruefen mutiert State (`$this->fieldvalue`, campaign_blockbooking.php:294); `$optionid` ungenutzt.

### `get_campaign_price(float $price, int $userid = 0): float` — public
- **Zweck:** No-op fuer diesen Typ — gibt Preis unveraendert zurueck (Interface-Pflicht). **Bewertung:** A (bewusste Leer-Implementierung).

### `apply_logic(booking_option_settings &$settings, stdClass &$dbrecord): void` — public
- **Zweck:** Haengt die instanziierte Kampagne an `settings->campaigns[]` und `dbrecord->campaigns[]`, damit `is_blocking()` spaeter mit gecachter Instanz laufen kann.
- **Seiteneffekte:** Mutiert beide Referenz-Objekte. **Bewertung:** A.

### `is_blocking(booking_option_settings $settings, int $userid): array` — public
- **Zweck:** Kernlogik: bestimmt anhand blockoperator (blockbelow/blockabove/blockalways), Belegung und ggf. Profilfeld-Match, ob die Option fuer den User blockiert ist; liefert `['status'=>bool,'label'=>string]`.
- **Parameter:** `$settings`, `$userid`. **Rueckgabe:** assoziatives Array.
- **Seiteneffekte:** `global $PAGE`; ruft `booking_context_helper::fix_booking_page_context` (mutiert Page-Context!); liest Belegung via `singleton_service::get_instance_of_booking_answers` + `booking_answers::count_places`; bei Profilfeld `campaigns_info::check_if_profilefield_applies`; `format_string` auf Label.
- **Aufrufkette:** Von Verfuegbarkeits-/Buchungs-Logik bei Anzeige/Buchungsversuch gerufen.
- **Bewertung:** C — gemischte Verantwortung + Seiteneffekt: Page-Context-Mutation in einer scheinbar reinen Pruef-Methode (campaign_blockbooking.php:344); 3 Rueckgabe-Exit-Punkte mit dupliziertem `['status'=>false,'label'=>'']`-Literal (Z.365, 385); `$bofieldname`-Zuweisung-in-Bedingung (Z.374) wird nie verwendet (verschleiert nur `!empty($this->cpfield)`).

### Triviale Akzessoren
- `get_campaign_limit(int $limit): int` — **private**, No-op (`return (int)$limit`), Interface-Hilfsmethode, ungenutzt erkennbar. Score A.
- `get_name_of_campaign(): string` — public, `return $this->name ?? ''`. Score A.
- `get_id_of_campaign(): int` — public, `return $this->id ?? 0`. Score A.
- `user_specific_price(): bool` — public, `return $this->userspecificprice`. Score A.
