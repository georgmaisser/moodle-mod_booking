# bulkoperations_table — Methoden-Doku
**Datei:** `classes/table/bulkoperations_table.php` · **LOC:** 126 · **Subsystem:** S10 · **Klassen-Score:** B / -
> [Subsystem-Doc](../../subsystems/S10_*.md)

## Klassenueberblick
`bulkoperations_table` ist eine `local_wunderbyte_table\wunderbyte_table`-Variante fuer die Bulk-Operationen-Ansicht auf Buchungsoptionen und -Vorlagen (Templates). Sie definiert nur zwei Spalten-Renderer: `col_text` (Anzeigename, mit Template-Sonderfall) und `col_action` (Edit-Link, mit korrekter returnurl-Aufloesung im AJAX-Kontext). Persistenz: keine eigene; arbeitet auf den Row-`$values`. Kollaborateure: `booking` (`get_all_cmids`), `singleton_service` (Booking-Settings je bookingid), `moodle_url`/`html_writer`, `$PAGE`/`$OUTPUT`. Bemerkenswert ist die bewusste Open-Redirect-Absicherung des Referers in `col_action`.

## Methoden

### `public function col_text($values)` — public
- **Zweck:** Liefert den Anzeigenamen; bei einer Vorlage (kein `bookingid`, aber `json`) den `templatename` aus dem JSON, sonst `text`, sonst `'-'`. **Seiteneffekte:** `json_decode($values->json)`; `format_string(...)` auf den gewaehlten Namen. **Rueckgabe:** String. **Bewertung:** B — korrekt; `json_decode` ohne Fehler-Check, aber durch die nachfolgenden `!empty`-Guards harmlos (ungueltiges JSON → `null` → faellt auf `text`/`'-'` durch).

### `public function col_action($values)` — public
- **Zweck:** Rendert einen Edit-Icon-Link nach `editoptions.php` — fuer echte Optionen mit deren cmid, fuer Vorlagen (ohne bookingid/cmid) mit `addastemplate=1` und einem Fallback-cmid. **Seiteneffekte:** Liest im AJAX-Kontext (`AJAX_SCRIPT`) den `HTTP_REFERER` und bereinigt ihn via `clean_param(..., PARAM_LOCALURL)` als returnurl (sonst `$PAGE->url->out(false)`); fuer Vorlagen `booking::get_all_cmids()` + `reset()` als „hoechste/zuletzt angelegte" cmid; fuer Optionen `singleton_service::get_instance_of_booking_settings_by_bookingid($values->bookingid)` fuer die cmid; baut den Link mit `html_writer::link` + `pix_icon`. **Rueckgabe:** HTML-Link oder `''` (kein cmid verfuegbar). **Bewertung:** B — sicherheitsbewusst (PARAM_LOCALURL gegen Open-Redirect, expliziter Kommentar) und robust (leere-cmids-Guard). Schwaeche ist die Template-Heuristik „beliebige cmid via `reset(get_all_cmids())`": Der Edit-Link einer Vorlage haengt an einer fachlich unzusammenhaengenden Booking-Instanz; bewusst gewaehlt, aber fragil (`get_all_cmids()`-Reihenfolge ≠ garantiert „neueste"), wie der Code-Kommentar selbst einraeumt.

## Bewertungs-Resümee
Kompakte Bulk-Tabelle mit nur zwei Renderern. Positiv: durchdachte, kommentierte Open-Redirect-Absicherung der returnurl im AJAX-Pfad und saubere Guards. Einzige fachliche Unschaerfe ist die „nimm irgendeine cmid"-Heuristik fuer Vorlagen, die das Plugin bewusst in Kauf nimmt. Keine funktionalen Fehler. Klassen-Score **B / -**.
