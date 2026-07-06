# categoryadd — Methoden-Doku
**Datei:** `categoryadd.php` · **LOC:** 122 · **Subsystem:** S21 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Controller fuer Anlegen / Bearbeiten / Loeschen einer Booking-Kategorie (`booking_category`) auf Kursebene. Vereint Form-Anzeige (`mod_booking_categories_form`) mit direktem DB-CRUD und Delete-Guards. Persistenz: schreibend/loeschend auf `booking_category`, lesend auf `course`, `booking_category`, `booking`. Kollaborateure: `$DB`, `$PAGE`, `$OUTPUT`, `context_course`, `mod_booking_categories_form` (aus `categoriesform.class.php`).

## Request-/Permission-Flow
1. Params: `courseid` (required), `cid` (optional, Kategorie-id zum Editieren), `delete` (optional Flag) (Z.29-31).
2. URL-Aufbau abhaengig davon, ob `cid` gesetzt ist (Z.33-42).
3. `context_course::instance($courseid)` (Z.44) und Kurs-Record (Z.46-48, `coursemisconf` bei Fehlschlag).
4. `require_login($courseid, false)` (Z.52) und `require_capability('mod/booking:addinstance', $context)` (Z.54) — Schreib-/Loeschrechte sind an die Instanz-Anlage-Capability gekoppelt.

## Code-Abschnitte (statt Methoden)

### Delete-Zweig (Z.60-80)
- **Zweck:** Loescht eine Kategorie, sofern erlaubt. Setzt `$candelete = true` und entzieht es, wenn (a) Subkategorien existieren (`booking_category` mit `cid = $cid`, Z.63-67 → `deletesubcategory`) oder (b) die Kategorie noch von Booking-Instanzen verwendet wird (`booking` mit `categoryid = $cid`, Z.69-73 → `usedinbooking`).
- **Seiteneffekte:** Bei `$candelete` → `$DB->delete_records('booking_category', ['id' => $cid])` (Z.76); danach immer `redirect($redirecturl, $delmessage, 5)`.
- **Bewertung:** B — Guards gegen verwaiste Sub-Kategorien und referenzierte Instanzen sind sinnvoll. Hinweis: Der „used in booking"-Guard prueft `categoryid = $cid` exakt — passt NICHT zur Substring-`LIKE`-Suche in `category.php` (Inkonsistenz im Kategorie-Zuordnungsmodell). Kein eigener `sesskey`-Check fuer die Loeschung (GET-getriggert), aber durch Capability-Gate abgedeckt.

### Form-Submit-Zweig (Z.82-113)
- **Zweck:** Instanziiert das Formular, laedt bei `cid` Default-Werte aus `booking_category`, und persistiert bei abgesendeten Daten (`get_data(true)` — slashed) Insert oder Update.
- **Seiteneffekte:** `$DB->get_record('booking_category', ['id' => $cid])` (Z.86); je nach `$category->id != ''` → `$DB->update_record` (Z.108) oder `$DB->insert_record` (Z.110); danach `redirect`.
- **Detail:** `$category->cid` (Parent) wird auf `0` gesetzt, falls die Kategorie sich selbst als Parent referenziert (`$cid == $data->id`, Z.100-104) — verhindert eine triviale Selbst-Zyklus-Zuordnung.
- **Bewertung:** C — `get_data(true)` liefert geslashte Daten (legacy), die direkt als DB-Record uebernommen werden. Insert/Update wird ueber `$category->id != ''` (String-Vergleich auf ein DB-Int-Feld) unterschieden — funktioniert, ist aber lose. Kein `format`/Trim auf `name`.

### Render (Z.115-121)
Setzt Heading, gibt Header aus, `set_data($defaultvalues)` + `display()`, Footer.

## Auffaelligkeiten
- **Z.44-52 (P3): Reihenfolge.** `context_course::instance` und `$PAGE->navbar->add` (Z.50) laufen VOR `require_login` (Z.52). Kontextaufbau vor dem Login ist meist unkritisch, idiomatisch waere `require_login` zuerst.
- Tiefe Verzweigung (Delete-/Cancel-/Submit-/Render-Pfade) in einem einzigen Skript ohne Funktionsstruktur; mittlere Wartbarkeit.

## Bewertungs-Resümee
Kompakter CRUD-Controller mit brauchbaren Delete-Guards (Sub-Kategorien + Referenz-Check) und einem Selbst-Parent-Schutz. Schwaechen: geslashte Direkt-Persistenz, lose Insert/Update-Unterscheidung, `require_login` nach Kontextaufbau sowie eine Modell-Inkonsistenz zur Substring-Suche in `category.php`. Funktional korrekt. Klassen-Score **C / P3**.
