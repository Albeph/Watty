<img src="./.images/IMG_3305.jpg"  width="10%"/> 

# Watty - a TAP project
## Introduzione
### Cos'è
Watty mira a fornire una soluzione per il monitoraggio e l'analisi dell'utilizzo dei vari elettrodomestici in tempo reale.
### Da dove nasce
Nasce dalla curiosità di scoprire quanto fossi impattante rispetto al consumo generale dell’abitazione
### Perché è importante? Observability!
- Efficienza : Il crescente numero di dispositivi elettronici comporta un notevole aumento della richiesta energetica, quindi diventa importante misurare l'efficenza degli elettrodomestici.
- Divisione equa del costo in bolletta : attraverso Watty è possibile misurare la somma della potenza istantanea richiesta dagli elettrodomestici in una zona, e calcolarne, da essa, il consumo in Wh.
    >Ad esempio, in una casa di studenti.
- Monitoraggio degli elettrodomestici Essenziali : Rilevatori di Gas/Allagamento/Gas Nocivi, Caldaia, Frigorifero, ecc.
## Funzionamento generale
Il sistema è composto da una pipeline di elaborazione dati progettata per acquisire, analizzare e visualizzare i consumi energetici, i dati vengono raccolti da prese intelligenti o interruttori smart . Watty è stato sviluppato dalle fondamenta per funzionare usando un client ed un server.
> N.B. Rimane la possibilità di far funzionare tutto in un unica macchina.
### Lato Client
Il cliente che, ipoteticamente, acquista il servizio viene fornito di un dispositivo a basso consumo energetico (es. Raspberry pi), che attraverso una configurazione guidata si interfaccia con i prodotti smart nella rete locale e ne cattura le informazioni necessarie, nel nostro caso la potenza. Queste informazioni vengono inviate tramite un pacchetto HTTP al server configurato.
### Lato server
È presente una pipeline formata da :
1. Acquisizione Dati: Logstash raccoglie i dati inviati dal dispositivo del client.
2. Distribuzione: Kafka trasmette i dati ai componenti successivi.
3. Elaborazione e Storage: Spark analizza, trasforma e salva (in formato json) i dati.
    > 3,5. Estrazione Log: uno script PHP che, leggendo i log creati da Spark, crea un file CSV contenente tutti i valori registrati.
4. Visualizzazione: Elasticsearch e Kibana permettono l'analisi del dato da parte dell'utente.
### Diagramma
<img src="./.images/TAP_Diagram.jpg"  width="100%"/>

## Porte attualmente assegnate
### Lato Server
- 2222 : SFTP Server, per la ricezione dei file di mapping
- 9001 : Logstash, per la ricezione dei dati utili al funzionamento dell'applicazione
- 9097 : PHP Log-Extractor
- 5601 : Kibana, Per la visualizzazione della Watty's Dashboard
### Lato Client
- 9095 : Pagina di configurazione Watty
- 9096 : Pagina per la simulazione dei sensori
## Setup
### IMPORTANTE, eseguire prima il setup del server, così da mettersi in ascolto del client (more on that later)
### Lato server
#### Requisiti: 
- Docker
#### Installazione
1. Copiare l'intero contenuto della cartella `_utility/server_side` all'interno del dispositivo server
2. Eseguire lo script `start.sh`
#### In caso sia necessario fermare il container
Usare lo script `stop.sh`, in quanto quest'ultimo comprende l'argomento '-v' il quale elimina il contenuto dei volumi dichiarati nel docker compose.
### Lato Client
#### Requisiti: 
- Docker 
#### Installazione
1. Copiare l'intero contenuto della cartella `_utility/client_side` all'interno del dispositivo client
2. Eseguire lo script start.sh
3. Navigare usando il browser su <ip_della_macchina>:porta
#### Configurazione
La configurazione è totalmente guidata, ma ecco alcune note:
- Quando vengono chiesti gli indirizzi ip è importante specificare pure le porte
    >Es: 192.168.11.2:9090
- È fortemente sconsigliato l'utilizzo dell'opzione "Ho già un file di configurazione" a meno che si abbia studiato per bene il funzionamento dell'intero progetto.
#### In caso si voglia provare Watty senza l'utilizzo di sensori reali
1. Eseguire lo script start.sh
2. Navigare usando il browser su <ip_della_macchina>:9096
3. Caricare i file richiesti
    > I file possono essere scaricati dopo aver creato stanze ed elettrodomestici nella pagina web corrispondente alla prima parte di configurazione guidata, con i tasti in fondo a quest'ultima
4. Scegliere gli stati degli elettrodomestici
    > N.B. none corrisponde ad un sensore non raggiungibile o non connesso
## Analisi di alcune parti interessanti
1. Quando viene avviato il docker compose sul server, quest'ultimo resterà in attesa di ricevere i file che permetteranno il mapping delle zone e degli elettrodomestici, finchè non gli verranno inviati nel secondo step della configurazione guidata
2. Il dispositivo client crea un container docker per ogni dispositivo che deve "ascoltare".
3. Il contanier di Elasticsearch è limitato a 6g, questo per evitare che la macchina vada in crash per la saturazione della RAM.
    >L’intero container, a pieno regime, consuma dai 9 ai 10 GB di RAM, quindi modificare il valore in base alle proprie esigenze.
## Training del modello
- Il training è stato eseguito usando RandomForestClassifier.

- Il modello è stato addestrato attraverso valori simulati (i valori sono stati scelti in base alla media delle schede tecniche di alcuni elettrodomestici moderni)

- In caso i valori non siano più attuali, è possibile modificare i parametri per generare nuovi valori simulati ed addestrare il modello su quest'ultimi.
### Anche questo è implementato attravero l'utilizzo di un container Docker
All'interno della cartella `_utility/addestramento`, sono presenti due script:
- build.sh
- start.sh

di cui è facile intuire lo scopo!
## Compatibilità
### Compatibilità degli elettrodomestici
Gli elettrodomestici di cui è possibile stimare il loro stato, al momento, sono:
- "Frigorifero"
- "Climatizzatore"
- "Forno"
- "Microonde"
- "TV"
- "Robot_Cucina"
- "Rilevatore_allagamento"
- "Rilevatore_gas"
- "Lampade"
- "Luci"
- "Sistema_surround"
- "Computer"
- "Monitor"
- "Asciugacapelli"
- "Caldaia"
- "Lavatrice"
- "Other"
### Compatibilità dei prodotti
- Tapo : Attualmente l'integrazione con l'ecosistema TAPO è limitata al solo dispositivo p110, perché è l'unico che io sia riuscito a testare
- Meross : Lo script è compatibile con tutte le prese smart, ma i limiti sull'utilizzo delle api, sono vaghi
#### È Possibile aggiungere compatibilità ai prodotti!
All’interno della directory `client_side/client_device/connections/` si trova la cartella `boilerplate-connect`. 
Sia lo script python che il Dockerfile, al suo interno, sono commentati per permettere una facile implementazione di ulteriori device.
Inoltre bisogna aggiornare anche lo script `0_cnfStEl.php` (situato in `client_side/client_device/`) alla riga 288, c'è una mini guida commentata, che spiega cosa aggiungere.
## Tecnologie utilizzate
- PHP : per la creazione di ogni della configurazione guidata lato client
- Python : per gli script che permettono di raccogliere i dati dai sensori e per pyspark che permette di interfacciarsi con spark attraverso python
- Logstash : Per l'ingestion dei dati (ed una "pulizia" del dato in ingresso)
- Kafka : Come message broker per la distribuzione dei dati.
- Spark : per la gestione profonda e la trasformazione del dato 
- Mlib : per il Training del modello e per l'utilizzo di quest'ultimo
- Elasticsearch : per l'indexing del dato
- Kibana : per visualizzare in modo chiaro e facilmente comprensibile i dati attraverso delle dashboard
- Linux : in particolare gli script bash!
- Docker : perché ha permesso di unire tutte queste tecnologie per renderle un unico servizio
    >P.S. Mai più senza!
