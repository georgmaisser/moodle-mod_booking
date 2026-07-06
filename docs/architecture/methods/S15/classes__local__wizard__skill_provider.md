# skill_provider — Methoden-Doku
**Datei:** `classes/local/wizard/skill_provider.php` · **LOC:** 126 · **Subsystem:** S15 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S15_*.md)

## Klassenueberblick
`skill_provider` ist der mod_booking-seitige Entrypoint, ueber den das Subplugin `bookingextension_agent` (KI-Wizard) die von mod_booking bereitgestellten „Skills" einsammelt. Die Klasse implementiert zwei Vertraege aus dem Agent-Plugin: `skill_provider_interface` (liefert Komponente, Skill-Liste, Diagnostics, Prompt-Packs, Issue-Code-Provider, Prompt-Guidance) und `skill_input_normalizer_provider_interface` (liefert einen provider-eigenen Input-Normalizer). Sie haelt keinen eigenen Zustand: alle Daten werden lazy beim Aufruf beschafft. Persistenz: keine. Kollaborateure: `skill_discovery` (statischer Discovery-/Caching-Mechanismus im Agent-Plugin), die einzelnen `skill_interface`-Instanzen, sowie `provider_skill_input_normalizer` (mod_booking-eigener Normalizer). Die Klasse ist ein duenner Adapter: sie kennt die Skills nicht namentlich, sondern aggregiert generisch das, was `skill_discovery` fuer Komponente `mod_booking` findet.

## Methoden

### `public function get_component(): string` — public
- **Zweck:** Liefert den Komponentennamen `'mod/booking'` als Identifikator des Providers. **Seiteneffekte:** keine. **Rueckgabe:** konstanter String. **Bewertung:** A — trivialer Konstanten-Getter. (Hinweis: Schreibweise `mod/booking` mit Slash statt Frankenstyle `mod_booking` — muss zum Erwartungsformat des Agent-Konsumenten passen, hier offenbar gewollt.)

### `public function get_skills(): array` — public
- **Zweck:** Beschafft alle konkreten Skill-Instanzen von mod_booking und gibt sie alphabetisch nach `get_name()` sortiert zurueck. **Seiteneffekte:** `skill_discovery::get_skill_instances('mod_booking')` (Discovery + ggf. Caching im Agent-Plugin), `usort` (in-place). **Rueckgabe:** `array<int,skill_interface>` (re-indiziert via `array_values`). **Bewertung:** A — klar, deterministische Sortierung fuer stabile Prompt-Reihenfolge.

### `public function get_discovery_diagnostics(): array` — public
- **Zweck:** Gibt die Diagnose-Meldungen des letzten `get_skills()`/Discovery-Laufs zurueck (z.B. uebersprungene oder fehlerhafte Skills). **Seiteneffekte:** keine eigenen; liest statischen Zustand `skill_discovery::get_last_diagnostics()`. **Rueckgabe:** `array<int,string>`. **Bewertung:** B — funktional korrekt, aber implizit gekoppelt an die Reihenfolge: liefert nur sinnvolle Daten, wenn zuvor `get_skill_instances` lief; bei reiner Diagnostics-Abfrage ohne vorherigen Discovery-Lauf ggf. leer/stale. Vertraglich beim Agent-Plugin so vorgesehen.

### `public function get_contextual_prompt_packs(): array` — public
- **Zweck:** Sammelt aus allen Skills deren optionale `get_contextual_prompt_packs()` ein, dedupliziert sie ueber das Feld `id` und gibt eine flache Liste eindeutiger Packs zurueck. **Seiteneffekte:** ruft intern `get_skills()` auf (erneuter Discovery-Lauf), `method_exists`-Check je Skill. **Rueckgabe:** `array<int,array<string,mixed>>` eindeutiger Packs. **Bewertung:** B — robuste, defensive Iteration (Typ-Checks, leere/duplizierte ids werden uebersprungen). Schwaeche: ruft `get_skills()` frisch auf, statt eine bereits vorhandene Liste wiederzuverwenden → bei mehrfachem Aufruf doppelte Discovery-Arbeit (P3-Mikro-Ineffizienz, fuer einen Request-einmaligen Prompt-Aufbau unkritisch).

### `public function get_issue_code_provider(): ?\bookingextension_agent\local\wizard\interfaces\issue_code_provider_interface` — public
- **Zweck:** Optionaler Issue-Code-Provider; mod_booking liefert keinen. **Seiteneffekte:** keine. **Rueckgabe:** stets `null`. **Bewertung:** A — bewusster Opt-out-Stub des Vertrags.

### `public function get_prompt_guidance(): array` — public
- **Zweck:** Optionale provider-globale Prompt-Guidance; hier leer (Guidance kommt per Skill, siehe Memory „Agent Guidance-Injection"). **Seiteneffekte:** keine. **Rueckgabe:** leeres Array. **Bewertung:** A — bewusster Leer-Stub.

### `public function get_skill_input_normalizer(): ?skill_input_normalizer_interface` — public
- **Zweck:** Liefert den mod_booking-eigenen Input-Normalizer fuer Skill-Eingaben. **Seiteneffekte:** instanziiert bei jedem Aufruf ein neues `provider_skill_input_normalizer`. **Rueckgabe:** `skill_input_normalizer_interface`-Instanz (nie null hier). **Bewertung:** A — einfache Factory; Normalizer ist offenbar zustandslos, daher New-per-call unkritisch.

## Bewertungs-Resümee
Sauberer, skill-agnostischer Adapter zwischen mod_booking und dem KI-Wizard-Subplugin. Implementiert die Provider-Vertraege vollstaendig, mehrere bewusste Opt-out-Stubs (Issue-Codes, Guidance). Einzige Schwaeche ist die Mehrfach-Discovery in `get_contextual_prompt_packs()` (ruft `get_skills()` erneut) sowie die implizite Reihenfolge-Kopplung von `get_discovery_diagnostics()` an einen vorausgehenden Discovery-Lauf — beides P3, funktional unkritisch. Klassen-Score **A / P3**.
