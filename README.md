Projekt:
Secure monay transfer
die webseite die du baust (setup.php) soll alle files wie htacces und index datei... anlegen.
umgebung: defcon (seite muss sichere bekannte methoden nutzen keine unsicheren java biblotheken oder so. bei setup gebe ich in der setup.php oberfläche 2 btc adressen an fee adresse and dark-money adresse
Ziel die webseite "verwaltet" eine neue wärung nahmens dark-money
funktionsweiße: es gibt genau 1x dark-monay. jeder neue nutzer generirt user ID (zufällig festgelegt) und password (nutzeer legt fest) und muss auserdem einen mfa (totp) konfiguriren und seine eigene btc wallet adresse angeben.
jeder neue nutzer bekommt anteil von dark-money dh bei 1 nutzer hat dieser 100% bei 2 jeder 50% usw.
nach erstellen eines kontos schaltet man diese frei indem man 11$ (10 zu dark-money und 1 zur fee adresse(passirt im hintergrund) überweißt. das heißt auch wenn ein nutzer exestirt hat der 100% dark-money=10$ bei 2 nutzern jeder 50% aber diese 50%=10$. folgende aktionen sollen möglich sein: 
1. einzahlen: man zahlt btc ein, der dark-money ist mehr btc wert, die anteile an dark money werden anderes verteil das es passt (passirt sozusagen automatisch dh nutzer bekommt nur ziel adresse und webseite überprüft ständig ob neue zahlung eintreffen und die ursprungsadresse wird endsprechend mehr anteil gegeben (userid)
2. auszahlen (max das was die person entspricht) user gibt in btc/$ an und das landet in seinem btc wallet automatisch (dark-money ist weniger wert und der user hat weniger anteil.
3. transfer zieluser id angeben und btc/$ menge und die anteile vom dark money werden nue verteilt (beim einen abzihen bei anderen drauftun)

wichtig immer wenn von btc zu dark money oder zurück kommt 1$ fee drauf zwischen user id kostet nix.
achte auf sauberes einheitliches eher dunkles dising und eindeutiges branding und eindeutigen leitfaden (uder wird  nicht mit übermengen text/steuerelementen konfrontirt sondern eindeutigen elementen und saubererer navigation. egal wie die setup.php aufgebaut ist (wie sie dateien erstellt) es soll eine free.txt generirt werden diese enthält infos die den nutzer über zweck informiren dh dieser text soll zu sehen sein wenn man bei google unter dem link den text ließt als auch wenn man neu auf die seite kommt als info. fülle die free.txt sauber mit einem beispiel. wichtifg ist auch ganze seite auf engisch. erarbeite nun das grobe konzept , im nächsten promt details und im übernächsten promt setup.php generiren
