# backup_booking_activity_structure_step — Methoden-Doku
**Datei:** `backup/moodle2/backup_booking_stepslib.php` · **LOC:** 321 · **Subsystem:** S24 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S24_backup_restore.md)

## Klassenueberblick
`backup_booking_activity_structure_step extends backup_activity_structure_step` — der einzige Strukturschritt des Booking-Backups. Die Klasse hat genau eine Methode, die den kompletten `backup_nested_element`-XML-Baum der Booking-Instanz aufbaut: Knoten + Felder definieren, Eltern-Kind-Hierarchie verdrahten, Source-Tables/-SQL anbinden, ID- und File-Annotationen setzen. Mehrere Zweige sind config-gegatet (Teachers, Prices, Entities, Subbookings) bzw. an die `userinfo`-Backup-Einstellung oder die Existenz fremder Plugins (`local_shopping_cart`, `local_entities`) gekoppelt. Persistenz: keine eigene — erzeugt den Backup-Strukturbaum, der spaeter gegen die DB ausgefuehrt wird. Kollaborateure: Backup-Framework (`backup::VAR_ACTIVITYID/VAR_PARENTID`), Tabellen `booking`, `booking_options`, `booking_answers`, `booking_optiondates(_teachers)`, `booking_category`, `booking_teachers`, `booking_tags`, `booking_other`, `booking_prices`, `booking_customfields`, `booking_subbooking_options`, `booking_history`, `local_entities_relations`, `local_shopping_cart_iteminfo`.

## Methoden

### `protected function define_structure()` — protected
- **Zweck:** Baut und liefert die komplette Backup-Struktur der Booking-Aktivitaet. Liest zunaechst die `userinfo`-Backup-Einstellung (Z.39), definiert dann ~18 `backup_nested_element`-Knoten (booking, options/option, answers/answer, optiondates/optiondate, optiondates_teachers, categories, teachers, tags, other, prices, entitiesrelations fuer options UND optiondates, shoppingcart-iteminfo, customfields, subbookingoptions, history), verdrahtet die Baumhierarchie (Z.198–238) und bindet die Datenquellen an. **Seiteneffekte:** mehrere `get_config('booking', ...)`- und `class_exists(...)`-Aufrufe steuern, welche Source-Tables/-SQL angehaengt werden; `prepare_activity_structure($booking)` registriert den Baum. **Rueckgabe:** `backup_nested_element` (Wurzel, in Standard-Aktivitaetsstruktur gewrappt). **Bewertung:** B — funktional umfangreich und korrekt strukturiert; Beobachtungen:
  - **Source-Gates:** `option` wird per `set_source_sql(... WHERE bookingid = ?)` immer geladen; `answer` nur wenn `$userinfo` gesetzt (Z.300–302). Teachers/optiondates_teachers (Z.253–257), prices (Z.260–267), entities (Z.281–292) und subbookings (Z.295–297) haengen an `duplicationrestore*`-Config-Flags. Knoten ohne gesetzte Source liefern beim Backup schlicht keine Daten — die Hierarchie bleibt valide.
  - **Annotation auch ohne Source:** `$answer->annotate_ids('user', 'userid')` (Z.305) wird unbedingt gesetzt, auch wenn `answer` ohne `$userinfo` keine Source hat — unkritisch, da ohne Datensaetze nichts annotiert wird.
  - **Kommentar-Inkonsistenz (Z.313):** Die File-Annotation `annotate_files('mod_booking', 'description', 'id')` traegt den Kommentar „this file area hasn't itemid", obwohl hier explizit `'id'` als itemid uebergeben wird (Copy-Paste aus den darueberliegenden `null`-Faellen). Reiner Doku-Defekt, kein Funktionsfehler.
  - **Cross-Plugin-Kopplung:** shoppingcart-iteminfo (`class_exists('local_shopping_cart\shopping_cart')`) und entities (`class_exists('local_entities\entitiesrelation_handler')`) werden defensiv nur bei vorhandenem Plugin angebunden — robust.
  - **Semesters/Holidays** werden bewusst NICHT mitgesichert (Kommentar Z.41), da site-weit.

### Triviale Properties
Keine — die Klasse haelt keinen Zustand ueber `define_structure()` hinaus.

## Bewertungs-Resümee
Solider, vollstaendiger Backup-Strukturschritt mit sauberer config-/userinfo-/plugin-gegateter Source-Anbindung und korrekten ID-/File-Annotationen. Einzig nennenswert: der irrefuehrende „hasn't itemid"-Kommentar an der `description`-File-Annotation (Doku-Defekt) und die unbedingte answer-`annotate_ids`-Zeile. Funktional korrekt; die Komplexitaet (langer, manuell gepflegter Feldkatalog) macht den Step pflegeintensiv (P2). Klassen-Score **B / P2**.
