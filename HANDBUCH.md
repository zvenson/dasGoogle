# SvenDasGoogle - Handbuch

## Einrichtung in 3 Schritten

---

### Schritt 1: Google API Key erstellen

1. Oeffne die **Google Cloud Console**:
   https://console.cloud.google.com/

2. Erstelle ein neues Projekt (oder waehle ein bestehendes):
   - Oben links auf das Projekt-Dropdown klicken
   - "Neues Projekt" waehlen
   - Name vergeben (z.B. "Mein Shop"), auf "Erstellen" klicken

3. **Places API aktivieren**:
   - Im linken Menue: "APIs und Dienste" > "Bibliothek"
   - Nach "Places API" suchen
   - Auf "Places API" klicken > "Aktivieren"

4. **API Key erstellen**:
   - Im linken Menue: "APIs und Dienste" > "Anmeldedaten"
   - Oben auf "+ Anmeldedaten erstellen" > "API-Schluessel"
   - Der Key wird sofort angezeigt - kopieren!

5. **Key einschraenken** (empfohlen):
   - Auf den gerade erstellten Key klicken
   - Unter "API-Einschraenkungen" > "Schluessel einschraenken"
   - Nur "Places API" auswaehlen
   - Speichern

> **Kosten:** Google stellt $200 Gratis-Guthaben pro Monat bereit.
> Ein Abruf kostet ca. $0,017. Mit dem Standard-Cache (6 Stunden)
> sind das nur ~4 Abrufe pro Tag - also praktisch kostenlos.

---

### Schritt 2: Google Place ID herausfinden

1. Oeffne den **Place ID Finder** von Google:
   https://developers.google.com/maps/documentation/places/web-service/place-id

2. Scrolle runter zur Karte mit dem Suchfeld

3. Gib den **Firmennamen** oder die **Adresse** ein
   (z.B. "Dishio Hamburg" oder "Veritaskai 3, 21079 Hamburg")

4. Klicke auf das Suchergebnis - die **Place ID** wird angezeigt

   Sie sieht so aus: `ChIJE2a6n2mRsUcxvHfwz7_dYzw`

5. Place ID kopieren!

---

### Schritt 3: Plugin konfigurieren

1. Im Shopware-Admin zu **Erweiterungen** > **Meine Erweiterungen** gehen

2. Bei "Google Business Bewertungen" auf **"..."** > **"Konfiguration"** klicken

3. Folgende Felder ausfuellen:

| Feld | Beschreibung | Beispiel |
|------|-------------|---------|
| **Google API Key** | Der Key aus Schritt 1 | `AIzaSyB1a2c3d4e5f6...` |
| **Google Place ID** | Die ID aus Schritt 2 | `ChIJE2a6n2mRsUcxvHfwz7_dYzw` |
| **Widget aktivieren** | Floating-Widget ein/aus | An |
| **Position** | Links oder Rechts | Rechts |
| **Vertikale Position** | Oben, Mitte oder Unten | Mitte |
| **Hintergrundfarbe** | Farbe des Widgets | #ffffff |
| **Cache-Dauer** | Wie oft neue Daten geholt werden (in Stunden) | 6 |

4. **Speichern** - fertig!

---

## Features

### Floating Widget
Erscheint automatisch auf jeder Seite (wenn aktiviert). Zeigt:
- Google-Logo mit Sternebewertung
- Klick oeffnet ein Panel mit den letzten Bewertungen
- Link zu allen Bewertungen auf Google

### CMS Erlebniswelten-Element
Unter **Inhalte** > **Erlebniswelten** steht ein neuer Block "Google Bewertungen" zur Verfuegung:
- Horizontales Karussell mit den letzten Bewertungen
- Konfigurierbar: Anzahl der angezeigten Bewertungen (1-10)
- Header mit Gesamtbewertung ein/ausblendbar

---

## Haeufige Fragen

**Das Widget zeigt nichts an?**
- Pruefe ob API Key und Place ID korrekt eingetragen sind
- Pruefe ob die Places API in der Google Cloud Console aktiviert ist
- Cache leeren: Shopware Admin > Einstellungen > Caches & Indizes > "Alle loeschen"

**Wie oft werden die Bewertungen aktualisiert?**
- Standardmaessig alle 6 Stunden (einstellbar unter "Cache-Dauer")

**Wie viele Bewertungen werden angezeigt?**
- Google liefert maximal 5 Bewertungen ueber die API
- Im CMS-Element ist die Anzahl konfigurierbar (1-5)

**Fallen Kosten an?**
- Google bietet $200 Gratis-Guthaben pro Monat
- Bei 4 Abrufen pro Tag (6h Cache) kostet das ca. $0,07/Monat
- Fuer normale Shops also komplett kostenlos

---

## Entwickler & Support

Dieses Plugin wird entwickelt und gepflegt von **[Designburg.net](https://designburg.net)** –
Shopware-Agentur und Plugin-Werkstatt aus Hamburg.

- Webseite: <https://designburg.net>
- Kontakt / individuelle Anpassungen: [shopware@designburg.net](mailto:shopware@designburg.net)
- Bug-Reports & Feature-Requests: <https://github.com/zvenson/dasGoogle/issues>
