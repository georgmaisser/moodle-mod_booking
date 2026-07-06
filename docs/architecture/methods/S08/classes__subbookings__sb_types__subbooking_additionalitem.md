# subbooking_additionalitem — Methoden-Doku
**Datei:** `classes/subbookings/sb_types/subbooking_additionalitem.php` · **LOC:** 480 · **Subsystem:** S08 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S08_*.md)

## Klassenueberblick
`subbooking_additionalitem` implementiert das Interface `booking_subbooking` und repraesentiert einen optionalen Zusatzartikel (mit Preis und optionaler Abhaengigkeit von einem Custom-Form-Wert), der einer Booking-Option als Subbooking angehaengt werden kann. Die Klasse vereint mehrere Verantwortlichkeiten in einer Datei: Persistenz (DB-Read/Write auf `booking_subbooking_options`), Formular-Aufbau (mform), Default-Befuellung, Rendering-Interface und Block-/Buchungslogik. Hauptkollaborateure: `price` (Preisverwaltung), `customform`/`customformstore` (Form-Link-Abhaengigkeit), `singleton_service`, `subbooking_additionalitem_output` (Renderer) und Moodle File-API (`file_postupdate_standard_editor`/`file_prepare_standard_editor`).

## Methoden

### `set_subbookingdata(stdClass $record): void` — public
- **Zweck:** Uebernimmt id/block/optionid aus einem DB-Record und delegiert die JSON-Zerlegung.
- **Parameter:** `$record` Subbooking-DB-Record. **Rueckgabe:** void.
- **Seiteneffekte:** Setzt Objekt-Properties; ruft `set_subbookingdata_from_json`. Kein DB-Zugriff.
- **Aufrufkette:** Wird vom Subbooking-Loader/Factory beim Hydratisieren gerufen.
- **Bewertung:** A — schlanke Zuweisung.

### `set_subbookingdata_from_json(string $json): void` — public
- **Zweck:** Dekodiert den JSON-String und befuellt name/description/format/formlink-Properties.
- **Parameter:** `$json`. **Rueckgabe:** void.
- **Seiteneffekte:** `json_decode`; Property-Writes. Greift ungeschuetzt auf `$jsondata->name` zu (kein Null-Guard).
- **Aufrufkette:** Aus `set_subbookingdata`.
- **Bewertung:** B — `$jsondata->name` ohne `?? ''`-Guard waehrend andere Felder geguardet sind (datei:99), kleine Inkonsistenz/Fragilitaet.

### `add_subbooking_to_mform(MoodleQuickForm &$mform, array &$formdata): void` — public
- **Zweck:** Fuegt die Formularelemente fuer die Subbooking-Konfiguration hinzu (Beschreibung, Form-Link-Select, Link-Value, HTML-Editor, Preis).
- **Parameter:** `$mform` (by ref), `$formdata` (by ref, erwartet cmid/optionid/id). **Rueckgabe:** void.
- **Seiteneffekte:** `context_module::instance`, `singleton_service::get_instance_of_booking_option_settings`, `customform::return_formelements`, Instanziierung `price` + `add_price_to_mform`. Kein direkter DB-Write (settings/price lesen ggf. DB/Cache).
- **Aufrufkette:** Vom Subbooking-Konfigurationsformular.
- **Bewertung:** C — gemischte Verantwortung (Form-Aufbau + Settings-Lookup + Preis), statischer God-Call `singleton_service`/`customform`, ~60 LOC mit mehreren Belangen (datei:113-173).

### `get_name_of_subbooking($localized = true): string` — public
- **Zweck:** Liefert lokalisierten Typ-String oder den rohen Typnamen.
- **Bewertung:** A — Einzeiler.

### `save_subbooking(stdClass &$data): void` — public
- **Zweck:** Persistiert die Subbooking-Konfiguration: baut JSON, insert/update auf `booking_subbooking_options`, verarbeitet Editor-Files, speichert Preis.
- **Parameter:** `$data` (by ref, Formdaten). **Rueckgabe:** void.
- **Seiteneffekte:** `$DB->insert_record`/`$DB->update_record('booking_subbooking_options', ...)` — **zweifaches** update_record (datei:220/258), `file_postupdate_standard_editor` (Filearea `subbookings`), `price->save_from_form`. Liest `$USER`. Mutiert `$data` per Referenz.
- **Aufrufkette:** Vom Konfigurationsformular-Submit.
- **Bewertung:** D — ~72 LOC, gemischte Verantwortung (JSON-Bau + DB-Persistenz + File-Handling + Preis), doppelter DB-Write durch Description-Nachladen (Insert/Update + erneutes Update), schwer testbar (globals/DB/File-API verwoben) (datei:191-263).

### `set_defaults(stdClass &$data, stdClass $record): void` — public
- **Zweck:** Befuellt Formular-Defaults aus DB-Record (JSON entpacken, Editor vorbereiten, Preis-Daten setzen).
- **Seiteneffekte:** `json_decode`, `context_module::instance`, `file_prepare_standard_editor` (Filearea `subbookings`), `price->set_data`. Mutiert `$data` per Referenz. Kein DB-Write (Files/Price lesen).
- **Aufrufkette:** Beim Laden des Konfigurationsformulars.
- **Bewertung:** C — mehrere Belange (JSON, File-API, Preis), aber linear und mittellang; ungeguardeter Zugriff auf `$jsondata->description` etc. (datei:271-307).

### `return_interface(booking_option_settings $settings, int $userid): array` — public
- **Zweck:** Liefert Renderdaten + Template, aber nur fuer das letzte Item des Typs (Merge mehrerer Additionalitems in einen Container).
- **Seiteneffekte:** Keine DB/Cache; instanziiert `subbooking_additionalitem_output`.
- **Aufrufkette:** Vom Subbooking-Renderer der Buchungsmaske.
- **Bewertung:** B — die „nur letztes Item rendern"-Logik ist clever, aber implizit/fragil (Identitaetsvergleich `$lastitem !== $this`); duplizierter Kommentarblock (datei:318-322).

### `return_price($user): array` — public
- **Zweck:** Liefert Preis (ggf. veraendert bei Mehrfachwahl) via `price::get_price`.
- **Bewertung:** A — Delegation.

### `return_description($user): string` — public
- **Zweck:** Liefert die (statische) Beschreibung. Doc verspricht nutzerabhaengige Anpassung, Implementierung gibt nur `$this->description` zurueck.
- **Bewertung:** B — Stub/Doc-Drift, harmlos (datei:358-360).

### `return_subbooking_information(int $itemid = 0, int $userid = 0): array` — public
- **Zweck:** Liefert relevante Subbooking-Infos; hier leeres Array (kein Multi-Item-Split noetig).
- **Bewertung:** A — bewusster Stub.

### `return_answer_json(int $itemid, $user = null): string` — public
- **Zweck:** Optionale Zusatzwerte fuer die Answer-JSON; hier leerer String.
- **Bewertung:** A — Stub.

### `is_blocking(booking_option_settings $settings, int $userid = 0): bool` — public
- **Zweck:** Entscheidet, ob das Subbooking den Buchungsfluss blockiert: nicht anzeigen bei reiner waitforconfirmation; blockt wenn kein/kein passender Custom-Form-Wert vorliegt.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_answers`, `new customformstore` (liest gespeicherte Formwerte, ggf. Cache/DB).
- **Aufrufkette:** Aus der Availability-/Blocking-Pipeline der Buchungsmaske.
- **Bewertung:** C — verschachtelte Bedingungen, mehrere fruehe Returns mit invertierter Logik (datei:406-413), statischer God-Call; mittlere Komplexitaet aber nachvollziehbar (datei:398-436).

### `after_booking_action / reservation_action / reservation_deletion_action(...): bool` — public
- **Zweck:** Lifecycle-Hooks (nach Buchung / Reservierung / Reservierungsloeschung); alle geben unbedingt `true` zurueck.
- **Bewertung:** A — bewusste No-op-Stubs des Interfaces.

## Triviale Akzessoren
- Property-Defaults (id/optionid/type/typestringid/name/block/json/available/description/... als oeffentliche Felder, Z.45-79) — reine Datenhaltung, keine Logik.
