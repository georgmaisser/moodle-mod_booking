# alreadyreserved — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/alreadyreserved.php` · **LOC:** 302 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`alreadyreserved` implementiert `bo_condition` und repraesentiert den Status „Item liegt bereits im Warenkorb reserviert" (hartkodierte ID `MOD_BOOKING_BO_COND_ALREADYRESERVED`, in `bo_info` mit Status-ID 102 gefuehrt). Die Condition greift nur bei preisbehafteten Optionen (`jsonobject->useprice`): ist die Option kostenlos oder der User noch nicht reserviert, ist sie verfuegbar (true). Sie ist hartkodiert (nicht JSON-kompatibel), nicht im mform sichtbar und nicht skippable. Persistenz: keine eigene; liest Booking-Answers und Warenkorb-Zustand. Kollaborateure: `singleton_service` (Booking-Answers, Option-Settings, User, Booking-Settings), `local_shopping_cart` (`cartstore`, `shopping_cart` — optional via `class_exists`-Guard), `bo_info` (Button-Rendering/Billboard). Zwei oeffentliche Properties (`$id`, `$overwrittenbybillboard = false`).

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert die hartkodierte Condition-ID. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Markiert die Condition als hartkodiert (false). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Liefert den lokalisierten Namen (`bocondalreadyreserved`). **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Diese Condition ist nicht ueberspringbar (false). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Zeigt keine Form-Elemente (false). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Verfuegbar (true), wenn die Option keinen Preis hat ODER der User in den Booking-Informationen nicht als `iamreserved` gefuehrt wird; ansonsten false; `$not` invertiert. **Seiteneffekte:** `singleton_service::get_instance_of_booking_answers($settings)` + `return_all_booking_information($userid)` (Cache/DB-Last). **Rueckgabe:** bool. **Bewertung:** B — `global $DB` wird deklariert, aber nirgends genutzt (toter Import); die teure `return_all_booking_information()` wird auch im kostenlosen Fall (`useprice` leer) berechnet, obwohl ihr Ergebnis dann ungenutzt bleibt (kleiner vermeidbarer Aufwand).

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert leeres SQL-5-Tupel (`['', '', '', [], '']`) — diese Condition filtert keine Listen. **Seiteneffekte:** keine; `$params` bleibt unberuehrt. **Rueckgabe:** Array. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Liefert konstant true (harte Sperre, wenn bereits reserviert). **Seiteneffekte:** keine. **Rueckgabe:** bool. **Bewertung:** A.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Ermittelt Verfuegbarkeit und liefert `[isavailable, description, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_JUSTMYALERT]`; Beschreibung nur gesetzt, wenn nicht verfuegbar. **Seiteneffekte:** ruft `is_available()` (mit dessen DB-/Cache-Last). **Rueckgabe:** 4-Tupel-Array. **Bewertung:** B — der Default `$userid = null` wird unveraendert an `is_available(int $userid, ...)` durchgereicht; bei tatsaechlichem `null`-Aufruf greift unter `strict_types`-freiem Code die int-Coercion (null -> 0), was den falschen User pruefen koennte — in der Praxis wird `$userid` vom Aufrufer stets gesetzt.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op (hartkodierte Condition ohne Form-Elemente). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Keine Zwischenseite — liefert leeres Array. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Action-Button: bei nicht-elektivem Booking ggf. Wiederherstellen des Warenkorb-Items und Preis-Button (`mod_booking/bookit_price`); bei elektivem Booking ein „selected"-Alert ueber `bo_info::render_button`. **Seiteneffekte:** holt frische Option-Settings/User/Booking-Settings via `singleton_service`; **wenn `screstoreitemfromreserved`-Config gesetzt und `local_shopping_cart` vorhanden, wird das Item via `shopping_cart::add_item_to_cart()` (zurueck) in den Warenkorb gelegt** — ein zustandsaendernder Schreibzugriff innerhalb einer Render-Methode. **Rueckgabe:** `[templatename, data]`. **Bewertung:** B — der Cart-Mutationspfad ist durch `class_exists`- und Config-Guard sowie eine Vorab-Pruefung (`get_item` leer?) abgesichert und idempotent; dennoch ist ein Cart-Write als Seiteneffekt von „render_button" semantisch fragwuerdig und schwer testbar.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Liefert den passenden lokalisierten Beschreibungstext (full/short × available/notavailable); bei nicht verfuegbar plus `overwrittenbybillboard` plus vorhandenem Billboard-Text wird stattdessen der Billboard-Text zurueckgegeben. **Seiteneffekte:** ggf. `bo_info::apply_billboard()`; `get_string`. **Rueckgabe:** String. **Bewertung:** B — `$overwrittenbybillboard` ist hier hartkodiert `false`, der Billboard-Zweig damit toter Code in dieser Klasse (vermutlich nur fuer Subklassen/Konsistenz mit dem Condition-Muster vorgehalten).

## Bewertungs-Resümee
Funktional korrekte, kleine hartkodierte Status-Condition mit klaren No-op-Vertragsmethoden. Echte Bugs fehlen; die Schwaechen sind P3-Qualitaet: ungenutztes `global $DB`, das teure `return_all_booking_information()` auch im preislosen Pfad, der `$userid = null`-Default gegen einen `int`-Parameter und der nie aktive Billboard-Zweig. Der nennenswerteste Punkt ist der Warenkorb-Schreibzugriff als Seiteneffekt von `render_button()` (config-gated, idempotent, aber architektonisch fragwuerdig). Klassen-Score **B / P3**.
