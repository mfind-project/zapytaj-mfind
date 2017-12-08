#Zapytaj mfind
PHPowa aplikacja wysyłająca maile z pytaniami na `pomoc@mfind.pl` przez MailChimp oraz wysyłająca post do
sales-adaptera.

##Baza danych
Aplikacja łączy się z `mysql.int.mfind.pl` do bazy `zapytaj_mfind_pl`

##Konfiguracja lokalnie

Jeśli masz wyższy php zainstaluj wersje 5.6 ze wtyczką postgres.

Instalcja PHP na OSX:
```
brew install php56 --with-postgresql
```

Odpalanie:
```
php -S localhost:9000
```

Jeśli na starcie masz informacje związaną z wysłaną wiadomością jest to efekt innej wersji
php niż na serwerze.

##Konfiguracja
W pliku `config.ini.tmpl` jest templatka konfiguracji połączenia z bazą oraz sales-adapterem.

## Deploy

### for staging
Url for staging [zapytaj-testing.mfind.pl/](https://zapytaj-testing.mfind.pl/)
```
cap staging deploy
```

### for production
Url for production [zapytaj.mfind.pl/](https://zapytaj.mfind.pl/)
```
cap production deploy
```
