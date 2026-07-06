# credits — Methoden-Doku
**Datei:** `classes/option/fields/credits.php` · **LOC:** 162 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`credits` ist ein Option-Feld (`extends field_base`) unter dem Header `PRICE` (`$save = MOD_BOOKING_EXECUTION_NORMAL`, Kategorie `STANDARD`). Es legt fest, wie viele "Credits" eine Buchungsoption kostet, und ergaenzt einen **Answer-Hook** (`add_json_to_booking_answer`), der in die JSON-Spalte von `booking_answers` ein `paidwithcredits`-Flag schreibt, wenn der Nutzer mit Credits bezahlt hat. Das Form-Element erscheint nur, wenn die Booking-Instanz **kein Elective** ist und Config `bookwithcreditsactive` aktiv ist. Kollaborateure: `singleton_service` (Booking-Instanz per cmid), `cache` (MUC `confirmbooking` als Bezahl-Signal), `fields_info` (Header), `field_base::prepare_save_field` (Standard-Persistenz).

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Delegiert die Persistenz des `credits`-Werts vollstaendig an die Basisklasse. **Seiteneffekte:** Ruft `parent::prepare_save_field($formdata, $newoption, $updateparam, '')` (Standard-Spalten-Mapping + Change-Tracking). **Rueckgabe:** das Change-Array der Elternmethode. **Bewertung:** A — duenner, korrekter Passthrough.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt das `credits`-Textfeld (Typ `PARAM_INT`) hinzu, sofern die Instanz kein Elective ist und `bookwithcreditsactive` gesetzt ist. **Seiteneffekte:** `singleton_service::get_instance_of_booking_by_cmid($cmid)` (cmid aus `$formdata['cmid'] ?? 0`); optionaler Header; `$booking->is_elective()` + `get_config('booking','bookwithcreditsactive')`-Gate. **Bewertung:** B — sauber; bei `cmid = 0` (Fallback) liefert `get_instance_of_booking_by_cmid(0)` potenziell keine valide Instanz, was hier nur das Feld unterdrueckt, aber den fragilen Default offenlegt.

### `public static function add_json_to_booking_answer(stdClass &$newanswer, int $userid)` — public static
- **Zweck:** Setzt beim Buchen das Flag `paidwithcredits = 1` in der JSON-Spalte der Answer, falls der Confirm-Cache signalisiert, dass mit Credits bezahlt wurde. **Seiteneffekte:** `cache::make('mod_booking','confirmbooking')`; liest `$cache->get($userid)` (ein Array), prueft `isset($data[$cachekey])` mit `$cachekey = "{userid}_{optionid}_bookwithcredits"`. Bei Treffer: dekodiert vorhandenes `$newanswer->json` (oder neues stdClass), setzt `paidwithcredits = 1`, ruft `$cache->delete($cachekey)` und re-enkodiert `$newanswer->json`. **Rueckgabe:** void. **Bewertung:** C — **funktionaler Cache-Bug:** die Bezahl-Markierung liegt als Element `$cachekey` **innerhalb** des unter dem Schluessel `$userid` gespeicherten Arrays (`$cache->get($userid)`). Zum Aufraeumen wird aber `$cache->delete($cachekey)` aufgerufen — das loescht einen **eigenstaendigen** (nicht existierenden) Top-Level-Cache-Eintrag, **nicht** das Array-Element unter `$userid`. Das Signal bleibt also im Cache bestehen und kann bei einer **weiteren Buchung desselben Users fuer dieselbe Option** erneut greifen und faelschlich `paidwithcredits` setzen. Korrekt waere, das Element aus dem Array zu entfernen und `$cache->set($userid, $data)` (oder `$cache->delete($userid)`) aufzurufen. Zusaetzlich: bei bereits vorhandenem `$newanswer->json` wird `json_decode` ohne Fehlerpruefung verwendet (bei Null-Rueckgabe wuerde `->paidwithcredits` auf `null` fehlschlagen) — in der Praxis selten, aber ungeschuetzt.

### Triviale Properties
Sechs statische Konfigurations-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.46–78).

## Bewertungs-Resümee
Schlankes Credits-Feld mit korrektem Persistenz-Passthrough und bedingtem Form-Element. Der Answer-Hook `add_json_to_booking_answer` enthaelt jedoch einen echten Cache-Invalidations-Bug (`delete($cachekey)` raeumt das falsche Cache-Item, das Bezahl-Signal kann persistieren) sowie ungeschuetztes `json_decode`. Klassen-Score **C / P3**.
