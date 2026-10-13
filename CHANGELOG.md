# Changelog

Alle nennenswerten Änderungen an diesem Modul werden hier dokumentiert.
Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

## [1.12.3] - 2026-10-13

### Changed
- Modul umbenannt: „Destille" → **„Messwertrechner"** — verbundweite Umstellung aller DG65-Toolkit-Mitglieder von Behörden-/Personen-Namen auf Funktionsnamen (Dietmar, 13.10.2026, koordiniert aus der Automat-Sitzung, siehe `TOOLKIT.md` Abschnitt „Umbenennung auf Funktionsnamen"). Schreibweise des Verbunds zugleich auf „DG65-Toolkit" (mit Bindestrich) vereinheitlicht.
- **Nur der Anzeigename geändert** (`library.json→name` = „DG65-Toolkit Messwertrechner", `module.json→aliases` auf genau einen Eintrag reduziert). Klassenname (`RollingAverage`), Präfix (`RAVG_`), Modulordner und Repo-URL bleiben bewusst vorerst unverändert — dieses Modul ist im Symcon Store gelistet, ein Klassenname-/Präfix-Wechsel würde jede fremde Bestandsinstanz brechen. Der vollständige technische Rename ist lokal auf einem separaten Branch vorbereitet und wartet auf Dietmars Bestätigung, dass es keine fremden Installationen gibt.

## [1.12.2] - 2026-09-09

### Changed
- Modul umbenannt: „Gleitender Mittelwert" → **„Destille"** — Ergebnis eines gemeinsamen Namensfindungsprozesses aller vier DG65-Toolkit-Mitglieder (Dietmars Wunsch: zweideutig/anregend statt nur beschreibend). Sicherer, additiver Rename: nur `library.json→name` geändert + „Destille" als weiterer `module.json→aliases`-Eintrag ergänzt — Klassenname (`RollingAverage`), Modul-ID und damit alle Bestandsinstanzen bleiben unverändert (siehe [[ips-module-pitfalls]] Punkt 4: ein Klassenname-Rename würde jede bestehende Instanz brechen)
- README-Titel und Kurzbeschreibung an den neuen Namen angepasst, mit Namenshinweis für Bestandsnutzer

## [1.12.1] - 2026-09-08

### Changed
- `library.json`-Name auf „DG65 Toolkit Gleitender Mittelwert" umgestellt, README um „Teil des DG65 Toolkit"-Abschnitt ergänzt — nach Abstimmung mit StrukturHub und Dietmar (08.09.2026): GleitenderMittelwert und StrukturHub bilden das Gründungspaar eines neuen, energieunabhängigen Verbunds für domänenfreie Symcon-Bausteine (Abgrenzung zu NRG-Stack)

## [1.12.0] - 2026-09-08

### Added
- Neue Option „Ausreißer filtern" (Fenster-Methoden 0-8): verwirft einzelne Sensor-Ausrutscher, die um mehr als eine konfigurierbare Anzahl Standardabweichungen vom bisherigen Fenster-Durchschnitt abweichen, bevor sie den Mittelwert verzerren

## [1.11.0] - 2026-09-08

### Added
- Neue Berechnungsmethode „Formel (freier Ausdruck)" — eigener Rechenausdruck mit x1/x2 (Quelle/Quelle 2), Grundrechenarten, Vergleiche, Klammern, Funktionen (abs/round/floor/ceil/min/max/sqrt/pow/log/log10/exp/sin/cos/tan) und `if(bedingung,dann,sonst)`. Ausgewertet über einen eigenen, sicheren Ausdrucks-Parser (kein `eval()`, feste Funktions-Positivliste)

## [1.10.0] - 2026-09-08

### Added
- Neue Berechnungsmethode „Schwellwert mit Hysterese" (Ausgabe 0/1, zwei Schwellen „Schwelle Ein"/„Schwelle Aus", Richtung automatisch aus deren Verhältnis abgeleitet) — verhindert das Flattern eines einzelnen Schwellwerts nahe der Grenze

## [1.9.0] - 2026-09-08

### Added
- Zwei neue Berechnungsmethoden: „Änderungsrate (Trend)" (Änderung des Werts je Sekunde/Minute/Stunde/Tag, neue Spalte „Trend-Basis") und „Integration (Aufsummierung über Zeit)" (z. B. Watt → Wh/kWh per Trapezregel, neue Spalte „Integrations-Einstellung" mit täglichem/monatlichem/manuellem Reset)
- Neue öffentliche Funktion `RAVG_ResetChannel($id, $Bezeichnung)` — setzt einen Integrations-Kanal manuell zurück (z. B. für Ladezyklen/Abrechnungsperioden per Skript/Ereignis)

### Changed
- Formular/README/Doku-Panel um die beiden neuen Methoden ergänzt

## [1.8.0] - 2026-09-08

### Added
- Formular an die verbundweite NRG-Stack-Optik angeglichen: "👋 Wozu dieses Modul?"-Einführung, "🆕 Neu in Version X.Y"-Panel, allgemeines "📖 Dokumentation & Hilfe"-Panel mit Versionsnummer, Symcon-Forum-Hinweis und "🧡 Über dieses Modul"-Lizenz-/Spenden-Panel

### Changed
- `library.json`: Modulname von "Gleitender Mittelwert für IP-Symcon" auf "Gleitender Mittelwert" gekürzt (Store-Konvention: kein "IPS"/"Symcon" im Namen). Bewusst ohne "NRG-Stack"-Präfix — dieses Modul ist ein eigenständiges, generisches Rechenwerkzeug ohne Energie-/PV-Bezug, keine NRG-Stack-Marke
- README: Lizenzabschnitt korrigiert (stand noch auf MIT, obwohl die LICENSE-Datei bereits PolyForm Noncommercial 1.0.0 trägt)

## [1.7.1] - 2026-07-11

### Changed
- Autor in `library.json` von "Dietmar Gureth" auf "DG65" geändert

## [1.7.0] - 2026-07-11

### Added
- Formeln für jede der 8 Berechnungsmethoden im Doku-Panel und in der README ergänzt
- Kleines Diagramm je Berechnungsmethode (im Formular als eingebettetes Bild, in der README als Datei unter `docs/img/`) zur Veranschaulichung

## [1.6.0] - 2026-07-09

### Changed
- Profil-Zuweisung erfolgt jetzt nur noch bei der Erstanlage der Mittelwert-Variable (als Standardprofil über `RegisterVariableFloat`), statt bei jedem `ApplyChanges` per `IPS_SetVariableCustomProfile` zu überschreiben (Symcon-Review-Feedback: benutzerdefinierte Profile sind die Hoheit des Nutzers)

## [1.5.4] - 2026-07-09

### Changed
- Spalte "Quelle" nutzt jetzt `width: auto` und füllt den verbleibenden Platz der Liste — bessere Lesbarkeit langer Variablenpfade, ohne Pixel-Summen von Hand abstimmen zu müssen

## [1.5.3] - 2026-07-09

### Fixed
- Prozent-Spaltenbreiten aus 1.5.2 zurückgenommen — führten zu einer Liste, die breiter als der Bildschirm war, und schoben "Nachkommastellen" aus dem sichtbaren Bereich. Zurück zu festen, kompakteren Pixel-Breiten (Summe ~1160px), damit alle Spalten ohne horizontales Scrollen sichtbar sind

## [1.5.2] - 2026-07-09

### Changed
- Spaltenbreiten der Kanal-Liste auf Prozentwerte umgestellt, damit die Liste immer die volle Breite ausfüllt (gleiche Breite wie das Doku-Panel), unabhängig von der Bildschirmgröße

## [1.5.1] - 2026-07-09

### Changed
- Formular-Layout überarbeitet: Kanal-Liste geht wieder über die volle Breite (kein Nebeneinander mit dem Doku-Panel mehr), Doku-Panel steht jetzt oben und ist standardmäßig eingeklappt

## [1.5.0] - 2026-07-09

### Added
- Nachkommastellen pro Kanal konfigurierbar (0–4 oder "Automatisch"). Erzeugt bei fester Anzahl ein eigenes Nachkommastellen-Profil und überschreibt damit die automatische Profil-Übernahme — hilft besonders, wenn die Quellvariable selbst kein Profil besitzt (Standardformatierung zeigt sonst viele Nachkommastellen)

## [1.4.1] - 2026-07-07

### Fixed
- `vendor` in module.json auf leeren String korrigiert (Symcon-Review-Feedback): das Feld ist für den Hersteller des angebundenen Systems vorgesehen, nicht den Modulautor — für ein reines Hilfsmodul ohne angebundenes Fremdsystem bleibt es leer

## [1.4.0] - 2026-07-07

### Added
- Optionale zweite Quelle pro Kanal: Verknüpfung (Nur Quelle 1 / Addition / Subtraktion) und Invertieren, wirkt auf den Momentanwert **vor** der Pufferung/Mittelung. Ermöglicht z. B. einen PV-Überschuss-Mittelwert (−(Erzeugung + Bezug)) mit einem einzigen Kanal statt zwei getrennten Mittelwerten plus externer Kombination.

## [1.3.1] - 2026-07-03

### Fixed
- Profil der Quellvariable wird nur übernommen, wenn es selbst vom Typ Float ist — Quellen mit Integer-/Boolean-Profil (z. B. `GoodweET.WattEMS`) führten sonst zum Fehler "Variablentyp und Profiltyp stimmen nicht überein" beim Übernehmen

## [1.3.0] - 2026-07-03

### Added
- Weitere Berechnungsmethoden je Kanal: Median, Minimum, Maximum, Standardabweichung, Exponentiell gewichteter Mittelwert (EMA), Summe
- Doku-Panel neben der Kanal-Liste im Konfigurationsformular

## [1.2.0] - 2026-07-03

### Added
- Fenster-Dauer wählbar in Sekunden, Minuten, Stunden oder Tagen (statt nur Minuten)
- Zeitgewichteter Mittelwert als zusätzliche Berechnungsmethode

### Fixed
- Zeilen der Kanal-Liste sind jetzt per Drag & Drop sortierbar (`changeOrder` im Formular ergänzt)
- Variablen-Zuordnung erfolgt über gespeicherte Objekt-IDs statt Ident-Suche — bleibt dadurch auch beim Verschieben einer Mittelwert-Variable in den Objektbaum einer *anderen* Instanz korrekt erhalten

## [1.1.0] - 2026-07-03

### Added
- Mittelwert-Variable übernimmt automatisch das Profil (Einheit/Format) der Quellvariable

### Fixed
- Stabile Kanal-Kennung statt Listenposition — beim Umsortieren der Kanäle werden Variable und Ringpuffer nicht mehr vertauscht
- Variablen dürfen in Unterkategorien der eigenen Instanz verschoben werden, ohne dass beim nächsten Übernehmen eine doppelte Variable entsteht

## [1.0.0] - 2026-07-03

### Added
- Initiales Modul: konfigurierbare Mittelwert-Kanäle (Bezeichnung, Quelle, Fenster in Minuten)
- Archivfreie Berechnung über einen versteckten Ringpuffer (kein `AC_GetLoggedValues` nötig)
