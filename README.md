Is based on the code: [M0FXB](https://github.com/andrewmacrides-web/M0FXB-HAMTECH-Andreas/tree/main) - MOD by SP2ONG

-----------------------------------------------------------------------------------------------------------------------------------

**INSTALACJA**
```
sudo -s

# Jeśli nie masz git/apache/php zainstaluj:
apt-get update --allow-releaseinfo-change
apt-get install -y apache2 libapache2-mod-php php-cli apache2-utils git
systemctl restart apache2

rm -rf /tmp/ASL-dashboard
cd /tmp
git clone https://github.com/radioprj/ASL-dashboard.git
cd ASL-dashboard
rsync -av --exclude='.git*' ./ /var/www/html/

cd /var/www
chown -R www-data:www-data html
chmod -R g+rX html
chmod g+x html

# Kolektor działa jako użytkownik 'asterisk' - musi mieć dostęp do plików w /var/www/html
usermod -a -G www-data asterisk

cd /var/www/html
chown root:root asl-dashboard-collector.service
mv asl-dashboard-collector.service /etc/systemd/system/
systemctl daemon-reload

systemctl enable --now asl3-update-astdb.timer
systemctl start asl3-update-astdb.service

systemctl enable --now asl-dashboard-collector
```

Następnie ustaw uprawnienia www-data do wykonywania poleceń w Asterisku:
```
sudo -s
echo "www-data ALL=(root) NOPASSWD: /usr/sbin/asterisk" > /etc/sudoers.d/asl-dashboard
chmod 440 /etc/sudoers.d/asl-dashboard
visudo -cf /etc/sudoers.d/asl-dashboard >/dev/null
```
⚠️ Uwaga bezpieczeństwa: powyższa reguła daje kontu `www-data` (czyli serwerowi WWW) prawo do uruchamiania Asteriska z pełnymi uprawnieniami roota, bez ograniczenia argumentów. To świadomy kompromis, dzięki któremu dashboard może wysyłać komendy do repeatera — ale oznacza też, że każda luka w kodzie PHP tego dashboardu (obecna lub przyszła) daje w praktyce pełną kontrolę nad Asteriskiem. Jeśli wystawiasz ten dashboard poza swoją sieć domową, upewnij się, że sekcja `[security]` w `config.ini` (patrz niżej) jest poprawnie skonfigurowana.

Aktywuj w `rpt.conf` komendę `806` do rozłączania wszystkich nodów — potrzebne tylko jeśli chcesz korzystać z klawisza "Disconnect All":
```
sudo -s
nano /etc/asterisk/rpt.conf
# Poszukaj linii:
; 806 = ilink,6
# Usuń znak ; na początku, żeby wiersz wyglądał tak:
806 = ilink,6
# Zapisz: Ctrl+O, Enter, potem wyjdź: Ctrl+X
systemctl restart asterisk
```

**KONFIGURACJA**

```
cd /var/www/html/
sudo nano config.ini
```
Wpisz swój numer noda i znak wywoławczy, a także sekcję `[security]` — to lista sieci, z których połączenie z dashboardem pokazuje sekcję z makrami (Function Keys). **Bez tej sekcji nikt, nawet Ty z własnej sieci LAN, nie zobaczy przycisków** — to celowe, bezpieczne domyślne zachowanie (fail-closed):

```ini
[security]
internal_networks[] = "127.0.0.1/32"
internal_networks[] = "192.168.1.0/24"
```
Podmień `192.168.1.0/24` na realny zakres swojej sieci lokalnej.

Jeśli chcesz zmienić zawartość makr, edytuj:
```
sudo nano buttons.ini
```

**AKTUALIZACJA**

```
sudo -s
rm -rf /tmp/ASL-dashboard
cd /tmp
git clone https://github.com/radioprj/ASL-dashboard.git
cd ASL-dashboard
rsync -av --exclude='.git*' --exclude='config.ini' --exclude='buttons.ini' ./ /var/www/html/

# rsync jako root nadpisuje właściciela plików - trzeba przywrócić uprawnienia
chown -R www-data:www-data /var/www/html
chmod -R g+rX /var/www/html

# collector.php działa jako długo żyjący proces - same pliki na dysku
# się zaktualizowały, ale trzeba zrestartować proces, żeby wczytał nowy kod
systemctl restart asl-dashboard-collector
```


-----------------------------------------------------------------------------------------------------------------------------------


![ASL Dashboard](https://github.com/radioprj/ASL-Dashboard/blob/main/asl-dashboard.png)
