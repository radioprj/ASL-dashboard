Is based on the code: https://github.com/andrewmacrides-web/M0FXB-HAMTECH-Andreas/tree/main
MOD by SP2ONG

```
sudo -s
# Jeśli nie ma git zainstaluj oraz apache2
apt-get update --allow-releaseinfo-change
apt-get install -y apache2 libapache2-mod-php apache2-utils git
systemctl restart apache2
cd /tmp
git clone https://github.com/radioprj/ASL-dashboard.git
cd ASL-dashboard
rsync -av --exclude='.git*' ./ /var/www/html/
cd /var/www
chown -R www-data:www-data html
cd /var/www/html
chown root:root mv asl-dashboard-collector.service /etc/systemd/system/
mv asl-dashboard-collector.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now asl3-update-astdb.timer
systemctl start asl3-update-astdb.service 
systemctl enable --now asl-dashboard-collector
systemctl start asl-dashboard-collector
```
Następnie ustawić uprawnienia www-data do wykonania poleceń w Asterisk:
```
sudo -s
echo "www-data ALL=(root) NOPASSWD: /usr/sbin/asterisk" > /etc/sudoers.d/asl-dashboard
chmod 440 /etc/sudoers.d/asl-dashboard
visudo -cf /etc/sudoers.d/asl-dashboard >/dev/null
```
Aktywuj w rpt.conf komendę 806 do rozłączania nodów:
```
sudo -s
nano /etc/asterisk/rpt.conf
# Poszukaj linii
; 806 = ilink,6
# usuń znak ; aby wiersz był
806 = ilink,6
# zapisz używając klawiszy: Ctrl + O, a potem Enter i następnie wciśnij Ctrl + X
systemctl restart asterisk
```

**KONFUGURACJA**

W pliku config.ini są zakresy sieci wewnętrznych, z których połączenie z dashboard pokazuje sekcje z makrami.
Wpisać swój numer noda i znak:

```
cd /var/www/html/
nano config.ini
```


Jeśli chcesz, możesz zmienić zawartość makr w pliku:

```
nano buttons.ini
```

**AKTUALIZACJA**

```
sudo -s
cd /tmp
git clone https://github.com/radioprj/ASL-dashboard.git
cd ASL-dashboard
rsync -av --exclude='.git*' --exclude='config.ini' --exclude='buttons.ini' ./ /var/www/html/
```

![ASL Dashboard](https://github.com/radioprj/ASL-Dashboard/blob/main/asl-dashboard.png)
