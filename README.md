Is based on the code: https://github.com/andrewmacrides-web/M0FXB-HAMTECH-Andreas/tree/main
MOD by SP2ONG

```
sudo -s
# Jeśli nie ma git zainstaluj go oraz apache2
apt-get update --allow-releaseinfo-change
apt-get install -y apache2 libapache2-mod-php git
cd /var/www/html
git clone https://github.com/radioprj/ASL-dashboard.git
cd /var/www
chown -R www-data:www-data html
cd /var/www/html
mv asl-dashboard-collector.service /etc/systemd/system/
chown root:root /etc/systemd/system/asl-dashboard-collector.service
systemctl daemon-reload
systemctl enable --now asl3-update-astdb.timer
systemctl start asl3-update-astdb.service 
systemctl enable --now asl-dashboard-collector
systemctl status asl-dashboard-collector
```
Następnie wykonać:
```
sudo -s
echo "www-data ALL=(root) NOPASSWD: /usr/sbin/asterisk" > /etc/sudoers.d/asl-dashboard
chmod 440 /etc/sudoers.d/asl-dashboard
visudo -cf /etc/sudoers.d/asl-dashboard >/dev/null
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
cd /var/www/html
git pull origin main
```

![ASL Dashboard](https://github.com/radioprj/ASL-Dashboard/blob/main/asl-dashboard.png)
