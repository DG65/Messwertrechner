# Messwertrechner (vormals Destille, davor Gleitender Mittelwert)

![Symcon](https://img.shields.io/badge/Symcon-PHPModul-blue)
![Modul Version](https://img.shields.io/badge/Modul_Version-1.12.3-blue)
![Symcon Version](https://img.shields.io/badge/Symcon_Version-9.0%2B-blue)
![License](https://img.shields.io/badge/License-PolyForm_Noncommercial_1.0.0-lightgrey)
[![PayPal](https://img.shields.io/badge/PayPal-Me-blue?logo=paypal)](https://paypal.me/DietmarGureth)

IP-Symcon-Modul, das aus rohen, verrauschten Werten beliebiger Variablen laufend etwas Klareres destilliert: Mittelwert, Trend, Integration über Zeit, Schwellwert mit Hysterese, freie Formel — konfigurierbar über eine Liste von Kanälen, ganz ohne Abhängigkeit vom Archiv-System.

**Namenshinweis:** Das DG65-Toolkit stellt seine Mitglieder von Behörden-/Personen-Namen auf Funktionsnamen um (13.10.2026) — dieses Modul heißt jetzt **„Messwertrechner"** (Zwischenstand „Destille" bis 1.12.2, ursprünglich „Gleitender Mittelwert" bis 1.12.1). **Aktuell nur der Anzeigename geändert** (`library.json→name`, `module.json→aliases`) — Klassenname (`RollingAverage`), Präfix (`RAVG_`), Modulordner und Repo-URL bleiben vorerst unverändert, da dieses Modul im Symcon Store gelistet ist und erst nach Prüfung auf fremde Installationen vollständig technisch umgestellt werden darf (siehe `TOOLKIT.md`, Abschnitt „Umbenennung auf Funktionsnamen"). Modul-ID und alle Bestandsinstanzen bleiben in jedem Fall unverändert.

## DG65-Toolkit

Teil des **DG65-Toolkit** — domänenfreie, wiederverwendbare Symcon-Bausteine (im Unterschied zum energiespezifischen NRG-Stack desselben Autors). Weitere Mitglieder (Stand 13.10.2026, Namen teils ebenfalls im Umbau auf Funktionsnamen): Raumstruktur (Objektbaum-Organisation, vormals Katasteramt/StrukturHub), Formularschutz (Formular-Zugriffsschutz, vormals Pförtner/Türöffner), Gerätetausch (Modul-Migration, vormals Vormund/MigrationsHub), Anwesenheitssimulation (vormals Schein), Batteriewächter, Automat. Ein Toolkit-Modul funktioniert immer für sich allein und setzt kein anderes voraus — geteilt werden Entwicklungskonventionen, kein Laufzeit-Vertrag.

## Wofür braucht man das?

Rohe Live-Messwerte schwanken oft stärker, als es für Anzeige oder Steuerung sinnvoll ist. Ein gleitender Mittelwert glättet solche Schwankungen, ohne auf eine feste Zeitreihe (Stunde/Tag) beschränkt zu sein wie bei der Archiv-Aggregation. Typische Anwendungsfälle:

- **PV-/Batterieleistung glätten**: Wolken oder kurzzeitige Lastspitzen lassen die Momentanleistung springen; ein 5- oder 10-Minuten-Mittel gibt ein ruhigeres, aussagekräftigeres Bild für Anzeige oder Kachel.
- **Steuerungsentscheidungen entkoppeln**: Ein EMS, das z. B. Batterieladung an die Netzeinspeisung koppelt, sollte nicht auf jede Sekundenspitze reagieren (Regel-"Flattern", unnötige Schaltzyklen) — ein gemittelter Wert als Entscheidungsgrundlage ist stabiler.
- **Sensorrauschen filtern**: Temperatur-, Einstrahlungs- oder Leistungssensoren mit Messrauschen liefern über ein Zeitfenster gemittelt einen deutlich saubereren Wert.
- **Trends statt Momentaufnahmen**: Eine 24-Stunden-Außentemperatur oder ein 15-Minuten-Solar­einstrahlungswert zeigt die Tendenz, ohne dass ein einzelner Ausreißer die Anzeige verzerrt.
- **Überschussberechnung**: Mehrere geglättete Werte lassen sich kombinieren (z. B. PV-Überschuss = gemittelte Erzeugung minus gemittelter Verbrauch), ohne dass Momentan-Rauschen beider Seiten sich gegenseitig verstärkt.

## Warum kein Archiv?

Der naheliegende Ansatz für einen gleitenden Mittelwert ist `AC_GetLoggedValues` über die letzten N Minuten. Das hat in der Praxis zwei Nachteile:

- Er hängt von einem funktionierenden, korrekt konfigurierten Archiv ab (Logging muss für die Quelle aktiv sein, das Archiv-Modul muss verfügbar sein).
- Jede Berechnung fragt die komplette Archiv-Datenbank ab — unnötige Last für eine einfache laufende Mittelung.

Dieses Modul sampelt stattdessen den aktuellen Live-Wert der Quelle in einem festen Takt und hält die Werte in einem kleinen, versteckten Ringpuffer (JSON in einer String-Variable). Alte Einträge außerhalb des Fensters werden bei jedem Tick verworfen. Das funktioniert unabhängig davon, ob die Quelle überhaupt archiviert wird.

## Installation

1. In der IP-Symcon-Konsole: **Modulverwaltung → Hinzufügen** und die URL dieses Repositories eintragen: `https://github.com/DG65/NRGGleitenderMittelwert`
2. Eine neue Instanz vom Typ **„Gleitender Mittelwert"** anlegen.

## Konfiguration

| Feld | Bedeutung |
|---|---|
| Abtastintervall (Sekunden) | Wie oft die Quelle abgefragt und der Puffer aktualisiert wird (instanzweit, für alle Kanäle gleich) |
| Mittelwert-Kanäle | Liste der zu berechnenden Mittelwerte |

Pro Kanal:

| Spalte | Bedeutung |
|---|---|
| Bezeichnung | Name der erzeugten Mittelwert-Variable |
| Quelle | Die zu mittelnde Variable |
| Quelle 2 (optional) | Zweite Variable, die vor der Mittelung mit Quelle 1 verrechnet wird |
| Verknüpfung | Nur Quelle 1 / Quelle1 + Quelle2 / Quelle1 − Quelle2 |
| Invertieren | Kehrt das Vorzeichen des verrechneten Momentanwerts um, bevor er in den Puffer geht |
| Fenster | Zahlenwert der Fenster-Dauer (bei „Integration" ohne Wirkung — siehe unten) |
| Einheit | Sekunden / Minuten / Stunden / Tage (bei „Integration" ohne Wirkung) |
| Methode | Berechnungsart (siehe unten) |
| Trend-Basis | Nur bei Methode „Änderungsrate": Zeitbasis der Ausgabe (je Sekunde/Minute/Stunde/Tag) |
| Integrations-Einstellung | Nur bei Methode „Integration": Reset-Rhythmus + Ausgabegröße (siehe unten) |
| Schwelle Ein / Schwelle Aus | Nur bei Methode „Schwellwert mit Hysterese": die beiden Umschaltpunkte (siehe unten) |
| Formel | Nur bei Methode „Formel": der Rechenausdruck mit x1/x2 (siehe unten) |
| Ausreißer filtern / Schwelle (σ) | Nur bei den Fenster-Methoden 0-8: verwirft einzelne Sensor-Ausrutscher (siehe unten) |
| Nachkommastellen | „Automatisch" übernimmt das Profil der Quelle (falls vorhanden); eine feste Zahl (0–4) überschreibt das und greift auch, wenn die Quelle kein Profil hat |

Zeilen können per Drag & Drop umsortiert werden.

### Ausreißer filtern

Optionale Vorverarbeitung für die Fenster-Methoden (Arithmetisch bis Änderungsrate, Mode 0-8): verwirft einen neuen Messwert, wenn er mehr als „Schwelle (σ)" Standardabweichungen vom Durchschnitt der bisherigen Fensterwerte abweicht — bevor der Ausreißer den Mittelwert kurzzeitig verzerrt. Braucht mindestens 3 vorhandene Werte im Fenster, um zu greifen (eine Standardabweichung aus 0-2 Werten ist statistisch nicht aussagekräftig). Der Ausreißer wird nicht in den Puffer aufgenommen, das Fenster altert aber normal weiter (alte Werte fallen wie gewohnt heraus, auch während gerade ein Ausreißer verworfen wird). Bei Integration/Hysterese/Formel (Mode 9-11) ohne Wirkung, da diese ohnehin nicht auf dem Fenster-Puffer arbeiten.

### Zwei Quellen verrechnen

Soll ein Mittelwert aus der Verknüpfung zweier Variablen gebildet werden (z. B. ein PV-Überschuss = −(Erzeugung + Bezug)), sollte man **erst verrechnen, dann mitteln** — nicht zwei getrennte Mittelwerte bilden und danach kombinieren. Das Modul macht genau das: Quelle 2, Verknüpfung und Invertieren wirken auf den Momentanwert, bevor er in den Ringpuffer geschrieben wird. Es reicht dafür ein einziger Kanal.

### Berechnungsmethoden

Alle Methoden arbeiten auf denselben gepufferten Werten `(t₁,v₁) … (tₙ,vₙ)` im konfigurierten Fenster — nur die Art der Zusammenfassung unterscheidet sich. `n` = Anzahl Werte im Fenster, `t_now` = aktueller Zeitpunkt.

**Arithmetisch**

`Ø = (v₁ + v₂ + … + vₙ) / n`

Jeder gesampelte Wert zählt gleich viel. Einfach und für die meisten Fälle ausreichend, solange das Abtastintervall zuverlässig eingehalten wird.

![Arithmetisch](docs/img/arithmetic.png)

**Zeitgewichtet**

`Ø = Σ(vᵢ · Δtᵢ) / Σ Δtᵢ`, wobei `Δtᵢ = t₍ᵢ₊₁₎ − tᵢ` (bzw. `t_now − tₙ` für den letzten Wert)

Jeder Wert zählt proportional zu der Zeitspanne, in der er tatsächlich galt. Dadurch verzerren verpasste Ticks, ein Neustart der Instanz oder unregelmäßige Abtastung das Ergebnis nicht — der Mittelwert entspricht dem tatsächlichen Zeitintegral über das Fenster, nicht nur dem Durchschnitt der Stichproben. Im Regelfall liefern Arithmetisch und Zeitgewichtet nahezu identische Ergebnisse; der Unterschied wird erst bei unregelmäßiger Taktung relevant.

![Zeitgewichtet](docs/img/timeweighted.png)

**Median**

Werte sortieren, dann der mittlere Wert (bei gerader Anzahl: Mittel der beiden mittleren Werte).

Robust gegen einzelne Ausreißer/Messfehler — ein kurzer Spike verzerrt den Wert nicht wie beim arithmetischen Mittel.

![Median](docs/img/median.png)

**Minimum / Maximum**

`min(v₁ … vₙ)` bzw. `max(v₁ … vₙ)`

Kleinster bzw. größter Wert im Fenster — z. B. Spitzenlast der letzten 10 Minuten oder niedrigster SOC-Wert über Nacht.

![Minimum / Maximum](docs/img/minmax.png)

**Standardabweichung**

`σ = √( Σ(vᵢ − Ø)² / n )`

Maß für die Schwankung/das Rauschen der Werte im Fenster selbst, nicht für deren mittleres Niveau.

![Standardabweichung](docs/img/stddev.png)

**Exponentiell (EMA)**

`EMAᵢ = EMA₍ᵢ₋₁₎ + α · (vᵢ − EMA₍ᵢ₋₁₎)`, mit `α = 1 − e^(−Δtᵢ/τ)` und Zeitkonstante `τ` = Fenster-Dauer

Neuere Werte zählen stärker als ältere (exponentiell abklingende Gewichtung). Das Fenster dient dabei als Zeitkonstante — größeres Fenster = träger reagierender Wert. Reagiert schneller auf echte Änderungen als ein starres Zeitfenster.

![Exponentiell](docs/img/ema.png)

**Summe**

`Σ = v₁ + v₂ + … + vₙ`

Aufsummierte Werte im Fenster statt eines Mittelwerts — beantwortet eine andere Frage (z. B. Gesamtenergie der letzten Stunde statt Durchschnittsleistung).

**Änderungsrate (Trend)**

`Rate = (vₙ − v₁) / (tₙ − t₁) · Zeitbasis`

Wie schnell steigt oder fällt der Wert im Fenster — z. B. Temperaturanstieg in °C/h, Luftdruckänderung in hPa/h oder Batterie-Lade-/Entladerate. Das „Fenster" bestimmt hier den Rückblick-Zeitraum, „Trend-Basis" die Zeiteinheit der Ausgabe (unabhängig vom Fenster wählbar). Braucht mindestens zwei Messpunkte im Fenster, sonst bleibt der Wert unverändert.

**Integration (Aufsummierung über Zeit)**

`Akkumulator += (vᵢ₋₁ + vᵢ) / 2 · Δtᵢ` (Trapezregel), Ausgabe = Akkumulator ÷ Divisor der gewählten „Integrations-Einstellung"

Summiert den Wert über die Zeit auf statt über ein festes Fenster zu mitteln — der klassische Fall ist eine Momentanleistung (W) ohne eigenen Energiezähler, aus der so eine Energiemenge (Wh/kWh) entsteht. Anders als bei den übrigen Methoden gelten „Fenster"/„Einheit" hier **nicht**: Die Summe läuft unbegrenzt weiter, bis entweder die gewählte „Integrations-Einstellung" einen automatischen Reset auslöst (täglich zur lokalen Mitternacht oder monatlich zum 1.) oder ein Skript/Ereignis `RAVG_ResetChannel($id, 'Bezeichnung')` aufruft (z. B. zum Start eines Ladezyklus oder einer Abrechnungsperiode). Bei „Nie automatisch" bleibt der Akkumulator bestehen, bis er manuell zurückgesetzt wird.

**Schwellwert mit Hysterese**

Ausgabe 0 (inaktiv) oder 1 (aktiv). Bleibt aktiv/inaktiv, bis der jeweils **andere** Schwellwert erreicht wird — verhindert das Flattern eines einzelnen Schwellwerts nahe der Grenze. Die Richtung ergibt sich automatisch aus dem Verhältnis der beiden Schwellen:

- **„Schwelle Ein" ≥ „Schwelle Aus"** → aktiv bei Überschreiten (z. B. PV-Überschuss: an ab 500 W, aus erst unter 200 W)
- **„Schwelle Ein" < „Schwelle Aus"** → aktiv bei Unterschreiten (z. B. SOC-Warnung: an ab 20 %, aus erst ab 30 %)

Wie bei „Integration" gelten „Fenster"/„Einheit" hier nicht — die Ausgabe reagiert direkt auf den Momentanwert der Quelle. Wer Glättung UND Hysterese kombinieren möchte, verkettet zwei Kanäle: ein Mittelwert-Kanal (z. B. Arithmetisch) als Quelle eines zweiten, nachgeschalteten Hysterese-Kanals.

**Formel (freier Ausdruck)**

Eigene Rechenformel in der Spalte „Formel", mit `x1` = Quelle und `x2` = Quelle 2 — **roh**, ohne „Verknüpfung"/„Invertieren" (die Formel übernimmt jede gewünschte Verrechnung selbst). Erlaubt:

- Grundrechenarten `+ − * / % ^` und Klammern
- Vergleiche `< <= > >= == !=` (liefern `1`/`0`)
- Funktionen: `abs round floor ceil min max sqrt pow log log10 exp sin cos tan` sowie `if(bedingung, dann, sonst)` für bedingte Ausdrücke

Beispiele:

| Formel | Ergebnis |
|---|---|
| `x1 * 9/5 + 32` | °C → °F |
| `x1 - x2` | Differenz zweier Quellen |
| `if(x1 > x2, x1, x2)` | Maximum als bedingter Ausdruck |
| `sqrt(x1^2 + x2^2)` | Betrag eines Vektors aus zwei Komponenten |

Wie bei Hysterese gelten „Fenster"/„Einheit" nicht. Bei einer ungültigen Formel (Syntaxfehler, Division durch 0, unbekannte Variable/Funktion) bleibt der bisherige Wert einfach stehen — kein Absturz, aber auch keine eigene Fehlermeldung im Formular; erkennbar daran, dass sich der Wert nach „Übernehmen" nicht mehr aktualisiert.

**Sicherheitshinweis:** Die Formel wird über einen eigenen, geschriebenen Ausdrucks-Parser ausgewertet — es kommt an keiner Stelle `eval()` zum Einsatz. Nur die oben genannten Funktionen sind erlaubt (feste Positivliste).

![Summe](docs/img/sum.png)

Pro Kanal legt das Modul zwei Variablen an:

- **Mittelwert** — die eigentliche Ausgabevariable, übernimmt automatisch das Profil (Einheit/Format) der Quellvariable.
- **Puffer** (versteckt) — interner Ringpuffer, nicht zur direkten Verwendung gedacht.

Beide Variablen dürfen frei im Objektbaum verschoben werden (auch in die Kategorie einer anderen Instanz) — das Modul verfolgt sie über ihre Objekt-ID, nicht über ihren Ort im Baum.

## Technische Hinweise

- Die Zuordnung Kanal → Variablen-ID liegt in einem internen Attribut, **nicht** in der sichtbaren Konfigurationsliste. Würde man sie dort ablegen, würde das in IP-Symcon das Drag & Drop der Liste sperren.
- Der Schlüssel eines Kanals ergibt sich aus Bezeichnung + Quelle. Ändert man eine der beiden bewusst, legt das Modul eine neue Variable an und räumt die alte auf — reines Umsortieren der Zeilen ändert daran nichts.
- Existierende Kanäle aus einer älteren Modulversion (nur „Fenster in Minuten", ohne Einheit) funktionieren unverändert weiter (Rückwärtskompatibilität).

## Lizenz

PolyForm Noncommercial 1.0.0 — privat und nicht-kommerziell frei nutzbar, siehe [LICENSE](LICENSE). Für den gewerblichen Einsatz ist eine gesonderte Lizenz vom Rechteinhaber nötig (Kontakt: dietmar@gureth.eu). Ältere Versionen bis zum Lizenzwechsel (siehe Changelog) bleiben unter MIT.

Gefällt dir das Modul und du möchtest trotzdem etwas dalassen? Über eine kleine Spende via [PayPal](https://paypal.me/DietmarGureth) freue ich mich — völlig freiwillig, keine Gegenleistung nötig.
