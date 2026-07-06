# slotrules — Methoden-Doku
**Datei:** `slotrules.php` · **LOC:** 333 · **Subsystem:** S21 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse, keine Funktionen) — der **Slot-Regel-Editor**. Vereint in einem einzigen Skript: Permission-Gate, Tabellen-Existenz-Pruefung, zwei Loesch-Bestaetigungs-Flows (Regel + Preis), das Laden/Vorbelegen einer zu editierenden Regel, das Speichern (Regel + optional Preis), und das Rendern der bestehenden Regeln als `html_table` (inkl. inline Preis-Summary). Persistenz: Tabellen `booking_slot_rule` + `booking_slot_rule_price`, durchgaengig ueber `mod_booking\local\slotbooking\slot_rule_manager` (`delete_rule`, `delete_rule_price`, `save_rule`, `save_rule_price`) sowie direkte `$DB`-Reads. Kollaborateure: `config.php`/`tablelib.php`, `context_module`, `slotrules_page_form`, `slot_rule_manager`, `$DB`, `$PAGE`/`$OUTPUT`/`html_writer`/`html_table`.

## Ablauf (Request-/Permission-Flow)
- **Eingangsparameter:** `id` (cmid), `optionid` (beide `required_param PARAM_INT`); `ruleid`, `deleteruleid`, `deletepriceid` (`optional_param PARAM_INT`); `confirmdelete`, `confirmdeleteprice` (`optional_param PARAM_BOOL`).
- **Auth-Gate (Z.42-45):** Kontext `context_module::instance($cm->id)`; verlangt `mod/booking:updatebooking` ODER `mod/booking:manageslotunavailability`, sonst Abbruch via `require_capability('mod/booking:updatebooking')`.
- **Tabellen-Gate:** `$hastables` prueft Existenz beider Tabellen via `xmldb_table`; ist false, wird spaeter nur eine Warnung (`slot_rule_tables_missing`) gezeigt und beendet.

### Loesch-Bestaetigungs-Flows (Z.60-112)
- **delete_rule (Z.60-85):** Bei `deleteruleid > 0 && confirm_sesskey()`: mit `confirmdelete` → `slot_rule_manager::delete_rule($deleteruleid)` + Redirect; sonst Bestaetigungsseite (`$OUTPUT->confirm`) mit Continue-URL (`confirmdelete=1` + sesskey) und `exit`.
- **delete_price (Z.87-112):** Analog fuer `deletepriceid` → `slot_rule_manager::delete_rule_price($deletepriceid)`.
- **Seiteneffekte:** Loescht DB-Records; Redirects/`exit`.
- **Bewertung:** D — **Sicherheits-Befund (P2, IDOR):** `delete_rule($deleteruleid)` und `delete_rule_price($deletepriceid)` werden nur gegen `sesskey` + die Capability *auf diesem cm-Kontext* geprueft, aber die uebergebene `deleteruleid`/`deletepriceid` ist eine **globale, nicht auf `$optionid`/`$context` eingeschraenkte** ID. Ein Nutzer mit `manageslotunavailability`/`updatebooking` auf irgendeiner Booking-Instanz kann durch Faelschen der ID Slot-Regeln/-Preise **fremder Optionen/Instanzen** loeschen. Der Lade-Pfad fuer das Editieren (Z.117) bindet korrekt `optionid` ein — der Loesch-Pfad nicht.

### Edit-Vorbelegung (Z.114-156)
- **Load:** Bei `ruleid > 0` liest `$DB->get_record('booking_slot_rule', ['id'=>$ruleid, 'optionid'=>$optionid], IGNORE_MISSING)` (korrekt option-gescoped), dann den zugehoerigen Preis-Record.
- **set_data:** Baut ein `$defaults`-`stdClass`: `ruletype`, `priority`, `useactiverange` (abgeleitet aus activefrom/until), Zeitfenster, sieben `weekday_N`-Checkboxen (aus CSV `weekdays`), sowie bei vorhandenem Preis `pricecategoryidentifier`/`pricemode`/`pricevalue`/`pricecurrency`.
- **Bewertung:** B — sorgfaeltige, defensive Typkonvertierung; option-gescopter Load.

### Save (Z.158-204)
- **Cancel:** `is_cancelled()` → Redirect `editoptions.php`.
- **Save:** Bei `$hastables && get_data()`: sammelt aktive Wochentage, baut `$ruledata` (Defaults: ruletype `RULETYPE_CLOSED`, priority 100; activefrom/until nur wenn `useactiverange`), `save_rule(...)` liefert `$savedruleid`. Bei `RULETYPE_PRICE`: sucht bestehenden Preis per `(ruleid, pricecategoryidentifier)`, baut `$pricedata` (mode Default `PRICEMODE_ABSOLUTE`) und `save_rule_price(...)`. Redirect mit `slot_rule_saved`.
- **Seiteneffekte:** DB-Insert/Update beider Tabellen.
- **Bewertung:** B — Upsert-Logik ueber `existingprice->id` korrekt; `optionid` wird hier fest aus dem Request-Param gesetzt (nicht aus der geladenen Regel), aber `save_rule` arbeitet auf `data->id`+`optionid` konsistent.

### Render bestehender Regeln (Z.206-333)
- Liest `$DB->get_records('booking_slot_rule', ['optionid'=>$optionid], 'priority DESC, id ASC')` und baut eine `html_table` mit Typ/Priority/Active-Range/Zeitfenster/Wochentagen/Preis-Summary/Aktionen.
- **Preis-Summary (Z.285-306):** Bei `RULETYPE_PRICE` wird **pro Regel-Zeile** `$DB->get_records('booking_slot_rule_price', ['ruleid'=>...])` aufgerufen → **N+1-Query** (P3). Werte werden via `s()` und `format_float()` escaped; Loesch-Links pro Preis mit sesskey.
- **Bewertung:** D — funktional korrekt und escaped, aber N+1 beim Tabellen-Render und insgesamt sehr lange, vermischte Verantwortlichkeiten (Controller+View+Persistenz-Mapping) in einem Skript.

## Bewertungs-Resümee
Funktionsreicher, aber stark ueberladener Editor-Endpoint mit korrekter Escaping- und Upsert-Logik. Zwei substanzielle Schwaechen: (1) **P2/P1 IDOR** — die Loesch-Pfade fuer Regeln und Preise scopen die uebergebene ID nicht auf `optionid`/Kontext, sodass cross-instance-Loeschungen moeglich sind (vor `delete_rule`/`delete_rule_price` sollte verifiziert werden, dass die Regel zu `$optionid` gehoert); (2) **P3 N+1** beim Preis-Summary im Render-Loop. Dazu allgemeine Hygiene (Controller/View/Mapping in einem 333-Zeilen-Skript). Klassen-Score **D / P1**.
