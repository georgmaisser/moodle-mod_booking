# priceisset — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/priceisset.php` · **LOC:** 340 · **Subsystem:** S03 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`priceisset` ist eine hardcodierte `bo_condition`-Implementierung (hardcoded id `MOD_BOOKING_BO_COND_PRICEISSET`). Sie regelt, dass eine Buchungsoption mit gesetztem Preis nicht ueber den normalen Buchungsweg, sondern nur ueber die Bezahlung (shopping_cart) buchbar ist. Kollaborateure: `price` (Preisermittlung), `singleton_service` (User/Settings/booking_answers), `bo_info` (Billboard), `modechecker`, `moodle_url`. Implementiert das `bo_condition`-Interface (is_available, hard_block, get_description, render_button, return_sql etc.).

## Methoden

### `get_id(): int` — public
- **Zweck:** Liefert die hardcodierte Condition-id (`$this->id`). **Rueckgabe:** int. **Seiteneffekte:** keine. **Aufrufkette:** von `bo_info`/Conditions-Registry. **Bewertung:** A.

### `is_json_compatible(): bool` — public
- **Zweck:** Markiert die Condition als nicht JSON-konfigurierbar (hardcoded). **Rueckgabe:** `false`. **Seiteneffekte:** keine. **Bewertung:** A.

### `is_shown_in_mform(): bool` — public
- **Zweck:** Gibt an, dass die Condition nicht im Options-Formular erscheint. **Rueckgabe:** `false`. **Seiteneffekte:** keine. **Bewertung:** A.

### `get_name(): string` — public
- **Zweck:** Lokalisierter Anzeigename (`bocondpriceisset`). **Rueckgabe:** string. **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `is_skippable(): bool` — public
- **Zweck:** Condition kann nicht uebersprungen werden. **Rueckgabe:** `false`. **Seiteneffekte:** keine. **Bewertung:** A.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernlogik — bestimmt, ob die normale (preisfreie) Buchung verfuegbar ist. Verfuegbar, wenn (a) `displayemptyprice` aus ist und der ermittelte Preis fuer den User effektiv 0 ist, oder (b) `priceisalwayson` aus ist und kein `useprice` gesetzt ist; optional invertiert via `$not`.
- **Parameter:** `$settings`, `$userid`, `$not`. **Rueckgabe:** bool.
- **Seiteneffekte:** `global $DB` deklariert aber ungenutzt (toter Import); `get_config('booking', …)` (2x Config-Read); `singleton_service::get_instance_of_user`; `price::get_price('option', …)` (kann DB/Preis-Lookups ausloesen).
- **Aufrufkette:** von `bo_info` Verfuegbarkeitskette und intern von `get_description`/`get_description_string`.
- **Bewertung:** C — `global $DB` deklariert, aber nie verwendet (priceisset.php:116, toter Code/irrefuehrend); verschachtelte Doppel-Config-Branches mit subtiler Logik (zwei unabhaengige if-Bloecke setzen denselben Flag), schwer testbar.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Interface-Pflichtmethode fuer SQL-Injection in Verfuegbarkeitsfilter; hier ohne Wirkung. **Rueckgabe:** leeres 5er-Array `['', '', '', [], '']`. **Seiteneffekte:** keine (Referenz `$params` unveraendert). **Bewertung:** A (No-op-Interface-Erfuellung).

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block ergaenzend zu `is_available`; hier immer `true` (Preisoption darf nicht ohne Bezahlung gebucht werden). **Rueckgabe:** `true`. **Seiteneffekte:** keine. **Aufrufkette:** von Buchungs-Vorab-Check. **Bewertung:** A.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungstext + Prepage-/Button-Typ. Sonderfall: ohne installiertes `local_shopping_cart` und mit `bookforothers`-Capability wird `MYALERT`-Button geliefert, sonst `MYBUTTON`.
- **Parameter:** `$settings`, `$userid`, `$full`, `$not`. **Rueckgabe:** `[bool, string, prepage, buttontype]`.
- **Seiteneffekte:** ruft `is_available`; `context_module::instance($settings->cmid)`; `class_exists('local_shopping_cart\shopping_cart')`; `has_capability('mod/booking:bookforothers', …)`.
- **Aufrufkette:** von `bo_info` Beschreibungs-Rendering; ruft `get_description_string`.
- **Bewertung:** B — gemischte Verantwortung (Verfuegbarkeit + Cross-Plugin-Check + Capability + Buttonwahl), aber kurz und lesbar.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0): void` — public
- **Zweck:** Interface-Hook; No-op, da hardcoded Condition keine Formularfelder hat. **Seiteneffekte:** keine. **Bewertung:** A.

### `render_page(int $optionid, int $userid = 0): array` — public
- **Zweck:** Optionale Prepage; liefert leere Default-Struktur (kein Template, Continue-Button aktiv). **Rueckgabe:** array `data/template/buttontype`. **Seiteneffekte:** keine. **Bewertung:** A.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Baut die Daten fuer das Preis-Buchungsbutton-Template (`mod_booking/bookit_price`); ermittelt Wartelisten-Status und behandelt `bookonlyondetailspage` (no-JS-Link auf optionview.php).
- **Parameter:** Settings, userid, full, not, fullwidth. **Rueckgabe:** `['mod_booking/bookit_price', $data]`.
- **Seiteneffekte:** `global $USER, $PAGE`; mehrere `singleton_service`-Lookups (settings, user, booking_answers); `return_booking_option_information`, `return_all_booking_information`; `get_config('booking','bookonlyondetailspage')`; `modechecker::use_special_details_page_treatment`; `$PAGE->url->out()`; baut `moodle_url`.
- **Aufrufkette:** von Booking-Button-Rendering / `bo_info`.
- **Bewertung:** C — ~53 LOC mit gemischter Verantwortung (Datenbeschaffung + Wartelisten-Branch + URL-Bau + Mode-Sonderbehandlung); mehrere statische Singleton-God-Calls, schwer isoliert testbar (priceisset.php:258-311).

### `get_description_string($isavailable, $full, $settings): string` — public
- **Zweck:** Liefert lokalisierten Beschreibungstext je nach Verfuegbarkeit und `$full`-Sicht (staff/student); beruecksichtigt Billboard-Override (hier praktisch nie aktiv, da `$overwrittenbybillboard = false`).
- **Parameter:** isavailable, full, settings (untypisiert). **Rueckgabe:** string. **Seiteneffekte:** `get_string` (4 Varianten); ggf. `bo_info::apply_billboard`.
- **Aufrufkette:** von `get_description`. **Bewertung:** B — Inline-Assignment in der `!empty($desc = …)`-Bedingung (priceisset.php:326) leicht ungewoehnlich, sonst klar.

## Triviale Akzessoren
Felder `$id` (hardcoded id) und `$overwrittenbybillboard = false` sind einfache Property-Zuweisungen; `get_id` als trivialer Getter (oben bereits einzeln gelistet, da Interface-Methode).
