# audit_booking_invariants — Methoden-Doku
**Datei:** `cli/audit_booking_invariants.php` · **LOC:** 299 · **Subsystem:** S22 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S22_*.md)

## Klassenueberblick
Prozeduraler CLI-Entry-Point (keine Klasse). Read-only DB-Invarianten-Audit fuer `booking_answers`: prueft nach Concurrency-/Lasttests (JMeter-Shopping-Cart-Plan), ob das Buchungs-/Wartelisten-Modell konsistent ist — keine Overbookings, keine ueberfuellte Warteliste, kein doppelter aktiver Answer-Record, optional verwaiste Reservierungen und nicht-eingeschriebene gebuchte User. Fuehrt ausschliesslich SELECTs aus (sicher gegen Live-Sites). Persistenz: nur lesend (`booking_options`, `booking_answers`, `booking`, `user_enrolments`, `enrol`). Kollaborateure: `$DB`, `clilib` (`cli_get_params`/`cli_error`), `mod/booking/lib.php` fuer die `MOD_BOOKING_STATUSPARAM_*`-Konstanten.

> Subsystem-Hinweis: Die Datei ist in CLASS_INDEX nicht gelistet. Als read-only DB-Konsistenz-Audit ist sie hier S22 (db_layer) zugeordnet (Alternative waere S21 entry_scripts; semantisch dominiert die DB-Integritaets-Domaene).

## Ablauf / Code-Abschnitte (statt Methoden)

### Bootstrap & Parameter (Z.47-103)
- `CLI_SCRIPT`, config.php, clilib, lib.php. `cli_get_params` mit Optionen `help`, `optionids`, `bookingid`, `courseid`, `reserved-ttl`, `check-enrolment`, `summary`, `format`. Unbekannte Optionen → `cli_error`. `--help` druckt ausfuehrliche Nutzung und `exit(0)`.
- **Bewertung:** A — vollstaendige, dokumentierte CLI-Schnittstelle inkl. Exit-Code-Vertrag (0 clean / 2 Verletzung / 1 Usage).

### Scope-Filter-Aufbau (Z.105-128)
- **Zweck:** Baut optionale WHERE-Fragmente auf Alias `bo` aus `optionids` (via `get_in_or_equal`, SQL_PARAMS_NAMED), `bookingid` und `courseid` (Subquery `bo.bookingid IN (SELECT b.id FROM {booking} b WHERE b.course = :courseid)`). Mehrere Filter werden per AND verschnitten.
- **Seiteneffekte:** Keine (nur String-/Param-Aufbau).
- **Bewertung:** A — durchgehend parametrisiert (named params), korrekte Intersection-Semantik.

### Status-Konstanten (Z.130-135)
`$placestates` = BOOKED+RESERVED (0,2 — Cart-Reservierung haelt bereits einen Platz), `$waitstate` = WAITINGLIST (1), `$activestates` = 0,1,2. Sauber aus den lib-Konstanten abgeleitet statt hartkodiert.

### INV1 — Overbooking (Z.140-155)
- **Zweck:** JOIN `booking_answers` mit `waitinglist IN (0,2)`, `SUM(COALESCE(places,1))` gruppiert pro Option, `HAVING occupied > maxanswers` (nur `limitanswers=1 AND maxanswers>0`). Default `places=1` per COALESCE deckt NULL-Places ab.
- **Bewertung:** A.

### INV2 — Wartelisten-Overflow (Z.157-172)
- **Zweck:** Wartelisten-Summe (`waitinglist=1`) gegen `maxoverbooking`; nur fuer `maxoverbooking >= 0` (–1 = unbegrenzt korrekt ausgeschlossen). `HAVING waiting > maxoverbooking`.
- **Bewertung:** A — die Unlimited-Sonderregel (-1) ist korrekt beruecksichtigt.

### INV3 — Doppelter aktiver Answer (Z.174-190)
- **Zweck:** Faengt die Doppel-Record-Race (z.B. gleichzeitig reserved UND booked): GROUP BY `optionid,userid` ueber `activestates`, `HAVING COUNT(*) > 1`. `sql_concat` fuer den DB-portablen uniqkey.
- **Bewertung:** A — exakt die kritische Concurrency-Verletzung aus der Lock-Domaene.

### INV4 — Verwaiste Reservierungen (opt-in, Z.192-211)
- **Zweck:** Bei `--reserved-ttl=SEC > 0`: RESERVED-Answers (`waitinglist=2`) aelter als `time()-ttl` als wahrscheinliche Orphans flaggen. Soft-Check (default aus).
- **Bewertung:** A — bewusst als heuristischer Opt-in markiert.

### INV5 — Gebucht-aber-nicht-eingeschrieben (opt-in, Z.213-234)
- **Zweck:** Bei `--check-enrolment`: BOOKED-User ohne Enrolment im Ziel-Kurs der Option (`bo.courseid > 0`, `NOT EXISTS` auf `user_enrolments`/`enrol`). Best-effort.
- **Bewertung:** B — bewusst best-effort: prueft Enrolment im Kurs generell (irgendeine `enrol`-Instanz), nicht die booking-spezifische Enrolment-Methode/Status (`ue.status`/aktive Instanz). Im Doc-Kommentar (Z.213) als best-effort deklariert; akzeptabel, kann false positives/negatives erzeugen.

### Summary & Output (Z.236-298)
- **Zweck:** Optionaler Per-Option-Occupancy-Report (LEFT JOIN, CASE-Summen) auch ohne Verletzung; danach JSON- oder Text-Ausgabe; `exit(0|2)` je nach `$violations`.
- **Bewertung:** A — sauberer Maschinen- (JSON) und Mensch-Pfad; Exit-Code als Build-Gate nutzbar.

## Auffaelligkeiten
- Keine funktionalen Bugs. Durchgehend read-only und parametrisiert. Einzige inhaerente Schwaeche ist die best-effort-Natur von INV5 (oben), die explizit dokumentiert ist.

## Bewertungs-Resümee
Sauberes, gut dokumentiertes Audit-Werkzeug: parametrisierte read-only Queries, korrekte Status-Modellierung (Cart-Reservierung verbraucht Platz; –1 = unlimited; NULL-places → 1), klarer Exit-Code-Vertrag fuer CI-/Lasttest-Integration. INV5 ist bewusst best-effort. Klassen-Score **A / P3**.
