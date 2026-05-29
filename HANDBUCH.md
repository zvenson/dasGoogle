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
| **Bewertungs-URL (optional)** | Leer = automatisch aus Place ID | (leer) |

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

### Bewertungseinladung per Mail (Flow Builder)

Das Plugin liefert ein fertiges Mail-Template **"Google Bewertungseinladung"**,
mit dem Sie Kunden nach Lieferung um eine Google-Bewertung bitten koennen.
Der Bewertungslink wird automatisch aus Ihrer Place ID gebildet - Sie muessen
also nichts manuell pflegen.

**Wichtig (Recht):** Eine Bewertungsaufforderung per Mail gilt in Deutschland
als Werbung im Sinne von § 7 UWG und ist nur zulaessig, wenn:

1. der Empfaenger **Bestandskunde** ist (er hat bei Ihnen gekauft - bei einer
   Mail nach Bestellung gegeben), UND
2. die Mail einen **klaren Hinweis** enthaelt, wie der Kunde dem Empfang
   widersprechen kann (im mitgelieferten Template enthalten - bitte bei
   Anpassungen nicht entfernen), UND
3. der Kunde dem Erhalt von Werbung **nicht widersprochen** hat.

Bei Unsicherheit halten Sie bitte Ruecksprache mit Ihrem Anwalt. Eine
saubere Alternative ist ein expliziter Opt-In im Checkout
("Ich moechte spaeter um eine Bewertung gebeten werden").

#### Schritt 1: Flow im Admin anlegen

1. Im Admin zu **Einstellungen** > **Flows** gehen
2. Auf **"Flow hinzufuegen"** klicken
3. **Name:** "Google Bewertung anfordern" (frei waehlbar)
4. **Trigger:** `Checkout > Order > State changed`
   (oder spezifischer: `Order delivery > State enter > Shipped`)
5. **Bedingung:** Sales Channel einschraenken, falls Sie nur fuer bestimmte
   Shops senden moechten

#### Schritt 2: Verzoegerung einbauen

Die Mail sollte **nicht** sofort bei Statuswechsel rausgehen, sondern erst,
wenn das Paket realistisch beim Kunden angekommen ist und benutzt wurde.

1. Im Flow einen Schritt **"Verzoegerung"** hinzufuegen
2. Wartezeit: **7-14 Tage** ist ein guter Wert
   (gibt dem Kunden Zeit, das Produkt tatsaechlich auszuprobieren)

#### Schritt 3: Mail senden

1. Nach der Verzoegerung den Schritt **"Mail versenden"** anhaengen
2. **Empfaenger:** "Kunde"
3. **Mail-Template:** "Google Bewertungseinladung" (das vom Plugin gelieferte)
4. Flow **aktivieren** und **speichern**

#### Optional: Bewertungs-URL ueberschreiben

In der Plugin-Konfiguration gibt es das Feld **"Bewertungs-URL (optional)"**.
Wenn leer, wird die URL automatisch aus der Place ID gebildet. Sie koennen
das Feld nutzen, wenn Sie z. B. auf eine andere Bewertungsseite verlinken
moechten (Trustpilot, eKomi, etc. - dann muessten Sie aber das Mail-Template
inhaltlich anpassen).

#### Verfuegbare Twig-Variablen im Template

| Variable | Beschreibung |
|----------|-------------|
| `{{ sdgReviewUrl }}` | Direktlink zur Google-Bewertungsseite (automatisch) |
| `{{ order.orderNumber }}` | Bestellnummer |
| `{{ order.orderCustomer.firstName }}` | Vorname des Kunden |
| `{{ order.orderCustomer.lastName }}` | Nachname des Kunden |
| `{{ salesChannel.translated.name }}` | Name des Shops |

Das Template laesst sich im Admin unter **Einstellungen** > **E-Mail-Templates**
frei anpassen.

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

## Admin-Modul "Google Bewertungen" (ab v2.3.0)

Im Admin gibt es unter **Marketing &rarr; Google Bewertungen** eine eigene
Uebersicht, in der Sie sehen koennen:

- **Wie viele Bewertungen sind aktuell in der lokalen DB**
- **Durchschnittliche Bewertung**
- **Welche Place ID gerade aktiv ist**
- **Tabelle aller gespeicherten Reviews** mit Autor, Sternebewertung, Text-Vorschau und Datum
- Filterung nach Mindest-Sterne
- Button **"Jetzt von Google holen"** &mdash; loescht den Cache, ruft Google neu ab
  und meldet *"X neue Bewertungen hinzugefuegt"*

So koennen Sie jederzeit pruefen, ob das Plugin tatsaechlich Reviews sammelt,
und manuell einen Refresh ausloesen.

---

## Order-Detail: Bewertungsmail-Status (ab v2.3.0)

Beim Oeffnen einer Bestellung im Admin sehen Sie in der rechten Seitenleiste
die Karte **"Google Bewertungseinladung"**:

- Anzeige des Status: **"Noch nicht versendet"** oder **"Versendet am DD.MM.YYYY HH:MM"**
- Button **"Bewertungsmail jetzt senden"** (bzw. "Erneut senden")

Der Versand laeuft sofort durch (kein Flow-Builder-Delay), nutzt aber dasselbe
Mail-Template wie der automatische Versand. Beim Klick erscheint eine
Bestaetigungs-Modal-Abfrage.

Das Tracking ("versendet am") funktioniert auch automatisch fuer Mails, die
ueber den Flow Builder rausgehen &mdash; Sie sehen also auch dort den Status.

---

## Entwickler & Support

Dieses Plugin wird entwickelt und gepflegt von **[Designburg.net](https://designburg.net)** –
Shopware-Agentur und Plugin-Werkstatt aus Hamburg.

- Webseite: <https://designburg.net>
- Kontakt / individuelle Anpassungen: [shopware@designburg.net](mailto:shopware@designburg.net)
- Bug-Reports & Feature-Requests: <https://github.com/zvenson/dasGoogle/issues>
