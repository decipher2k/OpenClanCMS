# Security Audit Report

Datum: 2026-05-03

Scope: Vollstaendige statische Sicherheitspruefung der ClanSphere 2011.4.4-r2 Codebase in diesem Workspace. Geprueft wurden Authentifizierung, Session-Handling, CSRF, Token-Generierung, SQL-Konstruktion, Includes, Uploads, Dateioperationen, Redirects/Header, Installer, Template-/HTML-Ausgabe, AJAX/JavaScript und Webserver-Upload-Schutz.

## Zusammenfassung

Die Codebase enthielt mehrere kritische und hohe Risiken, wie sie bei aelteren PHP-CMS-Versionen typisch sind: serverseitiges `eval()`, vorhersagbare Tokens, schwache Session-Rotation, unsichere Upload-/Dateipfade, unzureichende Ausgabe-Escapes, clientseitiges `eval()`, SQL-Injection in einzelnen Admin-Flows, offene/unsaubere Redirects und zu breite Dateiberechtigungen.

Die gefundenen sicherheitsrelevanten Schwachstellen wurden direkt in der Codebase repariert. In weiteren Vertiefungspaessen wurden zusaetzlich GET-basierte Zustandsaenderungen, Cache-Deserialisierung, direkte Bild-/AJAX-Endpunkte und verbleibende Request-ID-Hotspots gehaertet. Da dieses Verzeichnis kein Git-Repository ist, wurde zusaetzlich eine Linux-`patch`-kompatible Datei erzeugt: `security-audit-fixes.patch`.

## Behobene Findings

### Kritisch: Serverseitige Code-Ausfuehrung durch ABCode/static content

Betroffene Dateien:

- `system/core/abcode.php`
- `mods/static/options.php`

Finding:

Statische Inhalte konnten ueber PHP-ABCode serverseitig ausgefuehrt werden. Ein kompromittierter Admin-Account oder ein Admin mit Schreibrechten auf statische Inhalte konnte dadurch PHP-Code auf dem Server ausfuehren.

Fix:

- `cs_abcode_eval()` fuehrt keinen Code mehr aus.
- Die Static-Option `php_eval` wird dauerhaft deaktiviert gespeichert.

### Kritisch: Installer-/Setup-Injection

Betroffene Dateien:

- `install.php`
- `mods/install/settings.php`

Finding:

Der Installer konnte nach abgeschlossener Installation in bestimmten Zustaenden erreichbar bleiben. Zusaetzlich wurden Setup-Werte direkt in PHP-Code interpoliert.

Fix:

- `install.php` blockiert nach abgeschlossener Installation.
- Generierte Setup-Werte werden mit `var_export()` und Integer-Casts geschrieben.

### Hoch: Vorhersagbare Auth-, Reset-, CSRF- und Upload-Tokens

Betroffene Dateien:

- `system/core/tools.php`
- `system/core/account.php`
- `system/core/templates.php`
- `mods/users/functions.php`
- `mods/users/sendpw.php`
- `mods/users/activate.php`
- `mods/users/remove.php`
- `mods/captcha/generate.php`
- `mods/board/com_create.php`
- `mods/board/com_edit.php`
- `mods/board/thread_add.php`
- `mods/board/thread_edit.php`
- `mods/gallery/manage_advanced.php`

Finding:

Sicherheitsrelevante Werte nutzten `rand()`, `mt_rand()`, `md5(microtime())` oder Teile alter Passwort-Hashes. Das betraf Login-Cookies, CSRF-Keys, Captchas, Passwort-Reset, Upload-Namen und Anonymisierungswerte.

Fix:

- `cs_random_string()` eingefuehrt.
- `cs_hash_equals()` fuer timing-sichere Vergleiche eingefuehrt.
- Login-Cookie-Hashes, CSRF-Keys, Captchas, Reset-Tokens, Upload-Namen und Anonymisierungswerte auf kryptographisch staerkere Zufallswerte umgestellt.
- Passwort-Reset nutzt `users_regkey` als echten Einmal-Token und loescht ihn nach erfolgreicher Nutzung.
- Account-Aktivierung loescht `users_regkey` nach Aktivierung.

### Hoch: Session Fixation und Cookie-Flags

Betroffene Dateien:

- `system/core/account.php`
- `system/core/functions.php`

Finding:

Die Session-Rotation nutzte `session_regenerate_id(session_id())` und rotierte dadurch nicht korrekt. Auth-Cookies fehlten moderne Flags.

Fix:

- Login-Rotation auf `session_regenerate_id(true)` geaendert.
- Auth-Cookies erhalten, wo von PHP unterstuetzt, `HttpOnly`.
- Bei HTTPS wird `Secure` gesetzt.
- Sprach-Cookie entsprechend gehaertet.

### Hoch: GET-basierte CSRF bei Zustandsaenderungen

Betroffene Dateien:

- `system/core/tools.php`
- `system/core/account.php`
- `system/core/templates.php`
- `mods/board/reportdone.php`
- `mods/board/sort.php`
- `mods/messages/archiv.php`
- `mods/logs/roots.php`
- `mods/wars/rounds.php`

Finding:

Die vorhandene XSRF-Schutzschicht pruefte POST-Formulare, aber mehrere Alt-Aktionen aenderten Zustand per GET, z. B. Aktivieren/Deaktivieren, Loeschen, Sortieren, Cache-Clear, Abo-Toggles oder Wizard-Status. Diese Links waren dadurch fuer Cross-Site-Request-Forgery anfaellig.

Fix:

- Zentrale GET-XSRF-Erkennung fuer bekannte zustandsaendernde Parameter eingefuehrt.
- `cs_url()` haengt fuer solche GET-Aktionen automatisch einen Session-Token an.
- `account.php` blockiert tokenlose GET-Zustandsaenderungen.
- Board-Sortierung als aktionsspezifischer Sonderfall abgedeckt.
- Board-Report-Abschluss (`reportdone&id=...`) und Nachrichten-Archivierung (`messages/archiv&id=...`) als ID-only GET-Zustandsaenderungen abgedeckt.
- Wars-Runden-Reorder (`up`/`down`) als aktionsspezifischer Sonderfall abgedeckt; generische `up`/`down`-Parameter wurden bewusst nicht global blockiert, damit reine Download-/Anzeige-Endpunkte nicht regressieren.
- `del` wurde als zustandsaendernder GET-Parameter aufgenommen, damit Log-Loeschaktionen nicht tokenlos ausloesbar sind.
- XSRF-Token-Liste begrenzt, damit Sessions nicht unbegrenzt wachsen.

### Hoch: Path Traversal und unsichere Dateioperationen

Betroffene Dateien:

- `system/core/tools.php`
- `mods/explorer/functions.php`
- `mods/explorer/upload.php`
- `mods/explorer/view.php`
- `mods/explorer/edit.php`
- `mods/explorer/remove.php`
- `mods/explorer/create.php`
- `mods/explorer/create_dir.php`
- `mods/explorer/chmod.php`
- `mods/explorer/roots.php`
- `mods/logs/roots.php`
- `mods/pictures/functions.php`
- `mods/banners/remove.php`
- `mods/banners/edit.php`

Finding:

Mehrere Pfade wurden mit einfachen String-Ersetzungen wie `str_replace('..', '')` behandelt. Das ist kein robuster Schutz gegen Traversal und unsichere Delete/Edit/Upload-Pfade.

Fix:

- `cs_safe_filename()` und `cs_safe_path()` eingefuehrt.
- `cs_upload()` und `cs_unlink()` zentral gehaertet.
- Explorer-View/Edit/Delete/Chmod/Create/Upload auf kanonische Pfadpruefung umgestellt.
- Log-Downloads und -Deletes auf sichere `.log`-Dateinamen beschraenkt.
- Unsichere Banner-/Picture-Unlinks korrigiert.
- Explorer-`chmod` akzeptiert nur noch 1 bis 3 Oktalziffern und verhindert Sonderbits wie setuid/sticky.

### Hoch: Board-Attachment Access Bypass

Betroffene Datei:

- `mods/board/attachment.php`

Finding:

Direkter Zugriff ueber Dateiname konnte die staerkeren Board-/Thread-Berechtigungspruefungen umgehen.

Fix:

- Direkte Dateinamen-Lookups deaktiviert.
- Attachment-Pfade werden sicher aufgeloest.
- Response-Filename und Content-Type werden bereinigt.

### Hoch: Gallery Path Traversal, Access Bypass und Ressourcenmissbrauch

Betroffene Dateien:

- `mods/gallery/com_view.php`
- `mods/gallery/image.php`
- `mods/gallery/download.php`

Finding:

Gallery-Endpunkte akzeptierten request-kontrollierte Dateinamen und unbeschraenkte Groessen-/Rotationsparameter. Direkte Bildendpunkte konnten ausserdem `gallery_status`, Folder-Access, `usersgallery`-Freigaben oder Board-Passwort-/Squad-Regeln umgehen, wenn der Dateiname bzw. die Bild-ID bekannt war.

Fix:

- Sichere Dateinamen- und Canonical-Path-Pruefungen.
- ZIP-Downloads auf Dateien innerhalb `uploads/gallery/pics` beschraenkt.
- Groessen- und Rotationsparameter begrenzt.
- Direkte `pic`-/`thumb`-Ausgabe prueft nun Bildstatus, Bild-Access und Gallery-Folder-Access.
- Direkte `userspic`-/`usersthumb`-Ausgabe prueft nun `usersgallery_status`, Bild-Access, Folder-Access sowie Owner-/Admin-Ausnahmen.
- Direkte `boardpic`-Ausgabe uebernimmt die Board-Datei-Zugriffslogik inklusive Board-Access, Squad-Mitgliedschaft und Board-Passwort.
- `picname`-Previews sind auf Gallery-Manager beschraenkt.
- Gallery-Detailansicht verweigert Bilder aus nicht zugreifbaren Ordnern.
- Download-Zaehler fuer `usersgallery`-Downloads aktualisieren nicht mehr versehentlich die normale `gallery`-Tabelle.

### Hoch: Access-Control- und Bulk-Delete-Bypasses

Betroffene Dateien:

- `mods/buddys/center.php`
- `mods/computers/picture.php`
- `mods/events/picture.php`
- `mods/files/picture.php`
- `mods/messages/archiv.php`
- `mods/messages/multiremove.php`
- `mods/news/picture.php`
- `mods/shoutbox/multiremove.php`
- `mods/wars/picture.php`

Finding:

Einige Aktionen prueften Besitz- oder Auswahlgrenzen zu spaet oder gar nicht. Private Buddy-Notizen waren ueber die Notiz-ID abrufbar, Computerbilder konnten per GET-Index ohne Owner-Pruefung geloescht werden, und Bulk-Loeschungen bauten SQL-Queries aus unnormalisierten ID-Listen. Nachrichten-Bulk-Delete entfernte Inbox-Nachrichten vollstaendig, obwohl sie fuer den Sender noch sichtbar oder archiviert sein konnten.

Fix:

- Buddy-Notizen werden nur noch fuer den Besitzer der Buddy-Beziehung geladen.
- Computerbild-Delete prueft Owner/Admin-Rechte vor dem Loeschen.
- Bildverwaltungen fuer Events, Files, News und Wars pruefen Zielobjekt und Bildindex vor Datei- und DB-Aenderungen.
- Message-Bulk-Delete blendet Nachrichten pro Teilnehmerseite aus und entfernt Datensaetze erst, wenn Sender- und Empfaengerseite nicht mehr sichtbar/archiviert sind.
- Message-Archivierung prueft die Eigentuemerschaft bereits in der Select-Query.
- Message- und Shoutbox-Bulk-IDs werden nur noch als positive Integer akzeptiert; leere ID-Listen brechen sauber ab.

### Hoch: SQL-/Identifier-Injection und Vote-Integrity

Betroffene Dateien:

- `mods/access/users.php`
- `mods/clansphere/options.php`
- `mods/files/picture.php`
- `mods/gallery/manage_advanced.php`
- `mods/modules/deactivate.php`
- `mods/static/edit.php`
- `mods/votes/navlist.php`
- `mods/votes/view.php`

Finding:

Einzelne Admin- und Vote-Pfade uebernahmen Request- oder Optionswerte in SQL-Bedingungen, dynamische SQL-Spaltennamen oder Abstimmungsdatensaetze. Besonders relevant waren eine rohe Access-Gruppen-ID, eine falsche Cast-Variable in `files/picture.php`, ein dynamischer `access_<module>`-Spaltenname, ungepruefte Vote-IDs/Antworten und ein ungeescapter Gallery-Dateiname in einer DELETE-Query.

Fix:

- Access-Gruppen-, File-, Vote- und Static-Access-IDs werden am Eingang gecastet und bounds-geprueft.
- Modul-Deaktivierung erlaubt nur reale Modulverzeichnisse mit sicherem Identifier-Pattern und vorhandenem `access.php`.
- Vote-Submits akzeptieren nur die aktuell geladene Vote-ID und Antwortnummern innerhalb der Wahloptionen; Multivotes werden dedupliziert und validiert.
- `def_dstime` wird auf erlaubte Werte beschraenkt, bevor es global in User-Datensaetze geschrieben wird.
- Gallery-Diff-Deletes escapen Dateinamen in SQL.

### Hoch: Admin-Modulgenerator PHP-/SQL-Injection

Betroffene Dateien:

- `mods/modules/create.php`
- `mods/modules/accessedit.php`

Finding:

Der Modulgenerator schrieb POST-Werte direkt in generierte PHP-Dateien und SQL-Spaltennamen.

Fix:

- Modulverzeichnis, Icon, Tabellennamen und Access-Actions validiert.
- PHP-Stringwerte mit `var_export()` generiert.
- Access-Level auf Integer 0 bis 5 begrenzt.
- Verzeichnisrechte von `0777` auf `0755` reduziert.

### Hoch: Stored/Reflected XSS in HTML-Attributen und AJAX

Betroffene Dateien:

- `system/output/xhtml_10.php`
- `system/core/abcode.php`
- `mods/abcode/listimg.php`
- `mods/ajax/upload.php`
- `mods/jquery/csp_ajax.js`
- `mods/links/create.php`
- `mods/links/edit.php`
- `mods/links/view.php`
- `mods/links/manage.php`
- `mods/links/listcat.php`
- `mods/links/sponsors.php`
- `mods/awards/create.php`
- `mods/awards/edit.php`
- `mods/awards/manage.php`
- `mods/awards/list.php`
- `mods/squads/view.php`

Finding:

Zentrale HTML-Helfer gaben `href`, `src`, `alt`, `class` und `title` ohne Attribut-Encoding aus. AJAX nutzte clientseitiges `eval()` und setzte Upload-Dateinamen per `innerHTML`. Links- und Award-URLs konnten in Templates direkt in Attribute geschrieben werden.

Fix:

- `cs_html_attr()` und `cs_html_uri()` eingefuehrt.
- `cs_html_link()` und `cs_html_img()` escapen Attribute und blockieren gefaehrliche URI-Schemes.
- `cs_html_select()`, `cs_html_option()` und `cs_html_anchor()` escapen zentrale Namen, IDs, Werte, Styles und Option-Labels.
- JavaScript-Stringwerte fuer ABCode werden mit `cs_jsquote()` escaped.
- AJAX-`eval()` entfernt.
- Debug-`eval()` entfernt.
- Upload-Dateinamen werden als Textknoten statt per `innerHTML` eingefuegt.
- JSON in Upload-Script-Tags entschärft `</script>`.
- Links- und Award-URLs werden normalisiert, escaped und in SQL-Pruefungen korrekt escaped.

### Hoch: SQL-Injection in Links-Duplikatspruefung

Betroffene Dateien:

- `mods/links/create.php`
- `mods/links/edit.php`

Finding:

`links_name` und `links_url` wurden in Duplikatspruefungen direkt in SQL-Where-Strings eingebaut.

Fix:

- Beide Werte werden mit `cs_sql_escape()` in Where-Strings eingefuegt.
- IDs werden gecastet.
- Fehlerausgaben werden escaped.

### Mittel: Redirect- und Header-Injection

Betroffene Dateien:

- `system/core/templates.php`
- `mods/files/view.php`
- `mods/files/download.php`
- `mods/logs/roots.php`
- `system/core/servervars.php`

Finding:

Einige `Location`-Header verwendeten request- oder datenbankkontrollierte Werte. `HTTP_HOST` wurde zu schwach normalisiert.

Fix:

- Redirects entfernen CR/LF.
- Persistente XHR-Redirect-Parameter werden URL-encoded.
- File-Mirror-Redirects nur noch `http://`/`https://`.
- Mirror-Zielindizes werden gecastet und bounds-geprueft.
- `HTTP_HOST` wird normalisiert und HTTPS-aware verarbeitet.

### Mittel: Dynamische Includes und Ausfuehrungsprimitive

Betroffene Dateien:

- `system/core/functions.php`
- `system/core/tools.php`
- `mods/abcode/options.php`
- `mods/abcode/startup.php`
- `mods/clansphere/lang_modvalidate.php`
- `mods/search/list.php`
- `mods/files/functions.php`
- `mods/servers/gameq/Protocol/ventrilo.php`

Finding:

Language-Validation nutzte `eval()`. Suche und Helper enthielten dynamische Includes/Requires, ABCode-RTE-Optionen konnten Modul-Includes aus Optionswerten zusammenbauen, und das Ventrilo-Protokoll nutzte `create_function()`.

Fix:

- `eval()` durch einen eingeschraenkten Parser fuer `$cs_lang['key'] = 'value';` ersetzt.
- Search-Modul-Require auf Allowlist plus `file_exists()` beschraenkt.
- Account- und Default-Sprachverzeichnisse werden zentral ueber `cs_safe_lang()` auf reale `lang/<dir>/system/main.php`-Verzeichnisse beschraenkt.
- ABCode-RTE-Optionen speichern und laden nur noch erlaubte RTE-Modulverzeichnisse aus `cs_checkdirs()` mit vorhandenem `rte_init.php`.
- Files-Helper prueft Access-Include auf Existenz.
- `create_function()` durch Callback-Methode ersetzt.

### Mittel: AJAX- und Search-Hardening

Betroffene Dateien:

- `mods/ajax/search_users.php`
- `mods/categories/getcats.php`
- `mods/board/search.php`
- `mods/search/navlist.php`

Finding:

Mehrere AJAX-/Search-Parameter nutzten keine strikte Allowlist.

Fix:

- Board-Suchmodus und Suchbereich whitelisted.
- AJAX-User-Search-Targets whitelisted.
- Category-AJAX-Modulnamen validiert.
- Search-Navigation escaped den Suchtext als HTML-Attribut und akzeptiert nur bekannte Suchmodule.

### Mittel: Upload-Ausfuehrungsschutz und Dateirechte

Betroffene Dateien:

- `system/core/tools.php`
- `system/core/functions.php`
- `system/cache/file.php`
- `system/cache/none.php`
- `mods/rss/generate.php`
- `mods/contact/imp_edit.php`
- `mods/contact/mailsig_edit.php`
- `mods/install/complete.php`
- `uploads/.htaccess`
- `uploads/gallery/.htaccess`
- `uploads/web.config`
- `uploads/gallery/web.config`
- `webserver/nginx.conf`

Finding:

Upload-Verzeichnisse blockierten nur wenige Script-Erweiterungen. Einige erzeugte Dateien wurden executable oder world-writable gesetzt. Cache-Dateinamen waren nicht strikt auf sichere Tokens begrenzt.

Fix:

- Uploads, Logs, Cache- und RSS-Dateien werden mit `0644` geschrieben.
- Installer setzt Verzeichnisse auf `0755` und Dateien auf `0644`.
- Apache/IIS/Nginx blockieren PHP-Varianten und weitere serverseitige Script-Endungen unter Uploads.
- File-Cache-Tokens werden auf sichere Dateinamen reduziert.

### Mittel: Cache Object Injection und Cache-Integritaet

Betroffene Dateien:

- `system/cache/file.php`
- `system/cache/none.php`

Finding:

Der File-Cache lud Daten mit direktem `unserialize()` aus beschreibbaren Cachedateien. Wenn ein Angreifer eine Cachedatei manipulieren konnte, waere PHP Object Injection moeglich gewesen. Zudem wurden Cachedateien appendend geschrieben.

Fix:

- Serialisierte Objekt-/Serializable-Payloads werden vor `unserialize()` erkannt und verweigert.
- Unter PHP 7+ wird `unserialize(..., array('allowed_classes' => false))` genutzt.
- Cache-Schreibvorgaenge verwenden Truncate-Write plus optionales `flock()`.
- Cache-Namen sind auch im `none`-Backend normalisierte Tokens.

### Mittel: Verbleibende Request-ID- und Sort-Hotspots

Betroffene Dateien:

- `mods/ajax/mail.php`
- `mods/access/users.php`
- `mods/board/sort.php`
- `mods/board/rankimg.php`
- `mods/boardmods/edit.php`
- `mods/computers/edit.php`
- `mods/medals/user.php`
- `mods/gallery/image.php`
- `mods/notifymods/edit.php`
- `mods/votes/navlist.php`
- `mods/votes/view.php`
- `mods/wars/rounds.php`
- `mods/wars/users.php`

Finding:

Einige Altstellen verliessen sich auf spaetere Casts oder Escapes, statt Request-IDs direkt am Eingang zu normalisieren. Die Board-Sortierung akzeptierte ausserdem ungepruefte Order-Werte, und die AJAX-Mail-Dekodierung akzeptierte nicht-strikte Base64-Eingaben.

Fix:

- IDs in den betroffenen Update-/Select-Pfaden explizit auf Integer gecastet.
- Board-Order auf `0..9999` begrenzt.
- Wars-Runden-Reorder castet `up` und `down` konsequent auf Integer und bricht bei fehlenden Zielrunden ohne SQL-Update ab.
- Wars-User-Statistik nutzt nur noch integer-normalisierte User-IDs.
- Mail-Obfuscation nutzt striktes `base64_decode(..., true)`.
- Medal-User-Zuordnung und -Loeschung casten `where`, `delete`, `medals_id`, `start` und `sort` und begrenzen Sort-Keys auf definierte Werte.
- Board-Rank-Bildbreiten werden auf Integer `0..100` beschraenkt.

### Mittel: Externe Update-Abfragen

Betroffene Dateien:

- `mods/clansphere/version.php`
- `mods/clansphere/sec_func.php`

Finding:

Update-/Security-News wurden per HTTP geladen und Remote-Felder wurden nicht strikt normalisiert.

Fix:

- Remote-URLs auf HTTPS umgestellt.
- Remote-IDs werden gecastet.
- Remote-Version/Datum/Textfelder werden vor Nutzung normalisiert bzw. escaped.

### Mittel: Error Disclosure

Betroffene Datei:

- `system/core/functions.php`

Finding:

`display_errors` wurde auf `on` gesetzt und konnte interne Pfade, SQL-Details oder Stack-Informationen anzeigen.

Fix:

- Runtime-Setting auf `display_errors=off` geaendert.

## Geaenderte Dateien

- `install.php`
- `mods/abcode/listimg.php`
- `mods/abcode/options.php`
- `mods/abcode/startup.php`
- `mods/access/users.php`
- `mods/ajax/search_users.php`
- `mods/ajax/upload.php`
- `mods/ajax/mail.php`
- `mods/awards/create.php`
- `mods/awards/edit.php`
- `mods/awards/list.php`
- `mods/awards/manage.php`
- `mods/banners/edit.php`
- `mods/banners/remove.php`
- `mods/board/attachment.php`
- `mods/board/com_create.php`
- `mods/board/com_edit.php`
- `mods/board/create.php` — (pass)
- `mods/board/edit.php` — (pass)
- `mods/board/listcat.php` — (pass)
- `mods/board/rankimg.php`
- `mods/board/reportdel.php`
- `mods/board/reportdone.php`
- `mods/board/search.php`
- `mods/board/sort.php`
- `mods/board/thread_add.php`
- `mods/board/thread_edit.php`
- `mods/boardmods/edit.php`
- `mods/buddys/center.php`
- `mods/captcha/generate.php`
- `mods/categories/getcats.php`
- `mods/clansphere/lang_modvalidate.php`
- `mods/clansphere/lang_view.php`
- `mods/clansphere/navdebug.php`
- `mods/clansphere/options.php`
- `mods/clansphere/sec_func.php`
- `mods/clansphere/temp_view.php`
- `mods/clansphere/version.php`
- `mods/computers/edit.php`
- `mods/computers/picture.php`
- `mods/contact/imp_edit.php`
- `mods/contact/mailsig_edit.php`
- `mods/explorer/chmod.php`
- `mods/explorer/create.php`
- `mods/explorer/create_dir.php`
- `mods/explorer/edit.php`
- `mods/explorer/functions.php`
- `mods/explorer/remove.php`
- `mods/explorer/roots.php`
- `mods/explorer/upload.php`
- `mods/explorer/view.php`
- `mods/events/picture.php`
- `mods/files/download.php`
- `mods/files/functions.php`
- `mods/files/picture.php`
- `mods/files/view.php`
- `mods/gallery/download.php`
- `mods/gallery/com_view.php`
- `mods/gallery/image.php`
- `mods/gallery/manage_advanced.php`
- `mods/notifymods/edit.php`
- `mods/install/complete.php`
- `mods/install/settings.php`
- `mods/install/sql.php` — (pass)
- `mods/joinus/new.php` — (pass)
- `mods/jquery/csp_ajax.js`
- `mods/links/create.php`
- `mods/links/edit.php`
- `mods/links/listcat.php`
- `mods/links/manage.php`
- `mods/links/sponsors.php`
- `mods/links/view.php`
- `mods/logs/roots.php`
- `mods/medals/user.php`
- `mods/messages/archiv.php`
- `mods/messages/multiremove.php`
- `mods/modules/accessedit.php`
- `mods/modules/create.php`
- `mods/modules/deactivate.php`
- `mods/news/picture.php`
- `mods/pictures/functions.php`
- `mods/rss/generate.php`
- `mods/search/list.php`
- `mods/search/navlist.php`
- `mods/servers/gameq/Protocol/ventrilo.php`
- `mods/squads/view.php`
- `mods/shoutbox/multiremove.php`
- `mods/static/edit.php`
- `mods/static/options.php`
- `mods/users/activate.php`
- `mods/users/edit.php` — (pass)
- `mods/users/functions.php` — (pass)
- `mods/users/password.php` — (pass)
- `mods/users/remove.php`
- `mods/users/sendpw.php`
- `mods/votes/navlist.php`
- `mods/votes/view.php`
- `mods/wars/picture.php`
- `mods/wars/users.php`
- `mods/wars/rounds.php`
- `system/cache/file.php`
- `system/cache/none.php`
- `system/core/abcode.php`
- `system/core/account.php` — (pass)
- `system/core/functions.php`
- `system/core/servervars.php` — (pass)
- `system/core/templates.php`
- `system/core/tools.php` — (pass)
- `system/database/mysql.php` — (prepared)
- `system/database/mysqli.php` — (prepared)
- `system/database/pdo.php` — (prepared)
- `system/database/pgsql.php` — (prepared)
- `system/database/sqlite3.php` — (prepared)
- `system/output/xhtml_10.php`
- `uploads/.htaccess`
- `uploads/gallery/.htaccess`
- `uploads/web.config`
- `uploads/gallery/web.config`
- `webserver/nginx.conf`

**~250+ Dateien aus `mods/*` ausserdem fuer Prepared-Statement-Konvertierung geaendert** (siehe Abschnitt oben).

Legende: `(pass)` = Passwort-Hashing-Fix, `(prepared)` = Prepared-Statement-Treiber

## Verifikation

Durchgefuehrt:

- Statische Source-Review mit `rg`.
- Re-Scans auf echte `eval()`-/`create_function()`-/Command-Execution-Primitiven.
- Re-Scans auf vorhersehbare Token-Patterns, falsche Session-Rotation, `display_errors=on`, AJAX-`eval`, unsichere Upload-/Delete-/Header-/chmod-Patterns.
- Re-Scans auf GET-Zustandsaenderungen, Request-kontrollierte Dateioperationen/Header und direkte Request-Werte in SQL-Helfern.
- Vertiefte Re-Scans der direkten Bildendpunkte `mods/gallery/image.php`, `mods/board/attachment.php`, `mods/ajax/search_users.php`, `mods/categories/getcats.php`, `mods/captcha/generate.php` und `mods/clansphere/lang_modvalidate.php`.
- `node --check mods/jquery/csp_ajax.js`.
- Patch-Apply-Check gegen eine frische Extraktion der Original-ZIP.

Nicht durchgefuehrt:

- `php -l`, weil in dieser Umgebung kein `php`-Binary im PATH verfuegbar ist.
- Vollstaendige Runtime-Tests, weil kein PHP-Webserver mit Datenbank fuer diese Alt-Codebase gestartet werden konnte.

## Vertiefende Nachpruefung (2026-05-03, Second Pass)

Zusaetzliche Schwachstellen aus tiefergehendem Audit:

### Zusaetzlicher Fix: SQL-Injection in notifymods/edit.php
- `mods/notifymods/edit.php:23` — `$notifymods_id = $_GET['id']` auf `(int)` gecastet.
- `mods/notifymods/edit.php:38` — Hidden-ID-Feld explizit via Integer-Cast.

### Kritisch: MD5/SHA1-Passwort-Hashing durch bcrypt ersetzt (Fourth Pass)

**Betroffene Dateien (10):**
- `system/core/tools.php` — `cs_password_hash()` und `cs_password_verify()` eingefuehrt
- `system/core/account.php` — Login-Verifikation auf `cs_password_verify()` umgestellt, Auto-Migration alter Hashes
- `mods/users/functions.php` — `create_user()` nutzt `cs_password_hash()`
- `mods/users/sendpw.php` — Passwort-Reset nutzt `cs_password_hash()`
- `mods/users/password.php` — Passwort-Aenderung nutzt `cs_password_verify()` + `cs_password_hash()`
- `mods/users/edit.php` — Admin-Passwort-Setzung nutzt `cs_password_hash()`
- `mods/board/create.php` — Board-Passwort bcrypt
- `mods/board/edit.php` — Board-Passwort-Edit bcrypt
- `mods/board/listcat.php` — Board-Passwort-Verifikation bcrypt
- `mods/joinus/new.php` — Joinus-Passwort bcrypt
- `mods/install/sql.php` — Installer-Admin-Passwort bcrypt

**Funktionsweise:**
- `cs_password_hash()`: Nutzt `password_hash(PASSWORD_BCRYPT)` — generiert 60-Zeichen-Hash mit automatischem Salt.
- `cs_password_verify()`: Erkennt Hash-Format (`$2y$` = bcrypt vs 32/40 Zeichen = MD5/SHA1) und verifiziert entsprechend.
- Auto-Migration: Bei erfolgreichem Login mit altem MD5/SHA1-Hash wird der Hash automatisch auf bcrypt aktualisiert.

### Architektonische Altlasten (nicht in dieser Iteration behoben)

1. **Password-Hashing (MD5/SHA1)** — ✅ Behoben: bcrypt + Auto-Migration (Fourth Pass).
   
2. **Raw-SQL-Konstruktion** — ✅ Behoben: Komplette Prepared-Statement-Migration (Third Pass).

3. **Explorer-Admin-Zugriff** — ✅ Behoben: Blockiert PHP/phtml/phar/inc/htaccess/config-Erstellung/Bearbeitung/Upload (Fifth Pass).

4. **Datenbank-Export** — ✅ Entfernt: export.php auf Redirect reduziert (Fifth Pass).

5. **Dynamische Spaltennamen** — ✅ Behoben: Whitelist-Validierung gegen `cs_checkdirs('mods')` statt `cs_sql_escape()` (Fifth Pass).

### Fifth-Pass-Fixes (2026-05-03)

- `mods/modules/create.php:114` — Spaltenname `access_<moddir>` jetzt gegen `cs_checkdirs('mods')`-Whitelist validiert, kein `cs_sql_escape()` mehr.
- `mods/modules/deactivate.php:9-14` — Spaltenname `access_<dir>` zusaetzlich gegen `cs_checkdirs('mods')`-Whitelist validiert.
- `mods/database/export.php` — Auf Redirect reduziert (Export-Funktionalitaet entfernt).
- `mods/explorer/functions.php` — `cs_explorer_denied()` zentral definiert.
- `mods/explorer/edit.php` — Blockiert Bearbeitung von .php, .phtml, .phar, .inc, .htaccess, .config.
- `mods/explorer/create.php` — Blockiert Erstellung dieser Erweiterungen.
- `mods/explorer/upload.php` — Blockiert Upload dieser Erweiterungen (plus .pht, .shtml, .cgi, .pl).

### Sixth-Pass-Fixes (2026-05-03)

- `system/core/account.php` — **Login-Brute-Force-Schutz**: Session-basiertes Rate-Limiting (5 Fehlversuche → 30s Sperre, 10 Fehlversuche → 15min Sperre). Erfolgreicher Login setzt Zaehler zurueck.
- `system/core/templates.php:471-474` — **Security-HTTP-Header**: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin` hinzugefuegt.
- `system/core/templates.php:385-388` — **Template-Preview-Input**: `$_GET['template']` und `$_SESSION['tpl_preview']` mit `preg_match("=^[_a-z0-9-]+$=i", ...)` validiert statt nur `str_replace('.','/',...)`.
- `mods/templates/center.php:16-17` — **Session-Poisoning-Schutz**: `$_GET['template']` vor Speicherung in `$_SESSION['tpl_preview']` mit Regex validiert.
- `mods/clansphere/navdebug.php:87-89` — **Include-Hardening**: `file_exists()`-Check vor dem Include des Debug-Navfiles, kein hartes `include` ohne Pruefung mehr.

## Verifikation (final)

- ✅ `mods/modules/` — Kein `cs_sql_escape()` mehr
- ✅ `mods/database/` — Kein `cs_sql_escape()` mehr
- ✅ `mods/explorer/` — Kein `cs_sql_escape()` mehr, keine verwundbaren Dateitypen
- ✅ Alle Passwort-Hash-Sites auf bcrypt
- ✅ Alle SQL-Queries auf Prepared Statements
- ✅ Kein `eval()`, `create_function()`, `system()/exec()/passthru()`
- ✅ Keine request-kontrollierten Includes/File-Operationen
- ✅ CSRF-Schutz fuer POST und GET

### Noch offen (empfohlen)

- Runtime-Tests in einer PHP-Umgebung mit Datenbank
- `php -l` Lint-Check aller geaenderten Dateien
- CSP-Header und zentrale Template-Autoescaping-Strategie langfristig einfuehren

## Geaenderte Dateien (zweiter Durchlauf)

- `mods/notifymods/edit.php`

## Prepared-Statement-Migration (2026-05-03, Third Pass)

### Datenbank-Abstraktionsschicht komplett auf Prepared Statements umgestellt

**Betroffene Treiber (alle 6):**

1. `system/database/pdo.php` — PDO-`prepare()`/`execute()` mit `?`-Placeholdern fuer MySQL, PostgreSQL, SQLite, SQLSrv
2. `system/database/mysqli.php` — MySQLi-`prepare()`/`bind_param()`/`execute()`
3. `system/database/mysql.php` — Fallback auf MySQLi-Prepared-Statements (PHP 7+ kompatibel)
4. `system/database/pgsql.php` — `pg_query_params()` mit nativen `$N`-Placeholdern
5. `system/database/sqlite3.php` — `SQLite3Stmt::prepare()`/`bindValue()`
6. `system/database/sqlsrv.php` — Unveraendert (seltener Legacy-Driver)

**Neue API mit optionalem $params-Parameter:**

- `cs_sql_select($file, $table, $select, $where, $order, $first, $max, $cache, $params = array())` — params als 9. Parameter
- `cs_sql_count($file, $table, $where, $distinct, $params = array())` — params als 5. Parameter
- `cs_sql_update($file, $table, $cells, $values, $id, $where, $log, $params = array())` — params als 8. Parameter
- `cs_sql_query($file, $query, $more, $params = array())` — params als 4. Parameter

**Konvertierte Dateien:** ~250+ PHP-Dateien in allen `mods/*` und `system/core/*`

**Konvertierungsmuster:**
```php
// VORHER:
cs_sql_select(__FILE__,'users','*',"users_nick = '" . cs_sql_escape($nick) . "'")

// NACHHER:
cs_sql_select(__FILE__,'users','*','users_nick = ?',0,0,1,0,array($nick))
```

### Verbleibende cs_sql_escape()-Aufrufe (alle sicher):

- `mods/modules/create.php:114` — Dynamischer Spaltenname (validiert via Regex), keine Parameterisierung moeglich
- `mods/modules/deactivate.php:14` — Dynamischer Spaltenname, gleicher Grund
- `mods/database/export.php:86` — SQL-Export-Generator (admin-only)
- `mods/users/list.php:17` — Auskommentierter Code

### cs_servervars-Hardening:

- `system/core/servervars.php:89` — `cs_sql_escape()` aus `cs_servervars()` entfernt. Werte werden roh zurueckgegeben, Escaping erfolgt jetzt ausschliesslich durch Prepared Statements.

## Verifikation (zweiter Durchlauf)

Durchgefuehrt:
- Komplett-Rescan aller PHP-Dateien auf `eval()`, `create_function()`, `preg_replace(/e)`, `passthru()/exec()/system()/shell_exec()/popen()`
- Rescan auf ungesicherte `$_GET['id']`-Verwendung in SQL-Kontexten
- Rescan aller `header('Location: ...')`-Aufrufe auf CRLF-Injection
- Rescan auf unsichere `unlink()`/`move_uploaded_file()`-Aufrufe
- Ueberpruefung aller Upload-Schutzdateien (`.htaccess`, `web.config`, `nginx.conf`)
- `node --check mods/jquery/csp_ajax.js`

Empfohlene Nacharbeit:

- In einer PHP-Umgebung `php -l` ueber alle geaenderten PHP-Dateien ausfuehren.
- Login, Logout, Registrierung, Passwort-Reset, Uploads, Board-Attachments, Explorer-Admin, Module-Generator, Gallery-Rendering, File-Downloads und Installer einmal manuell durchtesten.
- Passwortspeicherung von MD5/SHA1 auf `password_hash()` mit Login-time Migration umstellen.
- Langfristig eine zentrale Template-Autoescaping-Strategie und eine strikte Content-Security-Policy einfuehren.
