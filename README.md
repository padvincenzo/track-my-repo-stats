# Track my repo stats
Keep track of my github repos release stats daily.

## Get started

1. Create the database (copy/paste code from ``database.sql`` into phpmyadmin)
2. Insert your repo names (the ones you want to track)

```sql
insert into project (name, slug) values
("My repo name", "my-repo-short");
```

3. Open ``repo-stats.php``
4. Update database credentials with yours

```php
// Database credentials
$host = "localhost";
$user = "root";
$password = "your-db-password";
$database = "your-db-name";
```

5. Update github username

```php
// Github username
$username = "mygithubusername";
```

6. Choose a password

```php
// Choose a password
$password = "123prova";
```

7. Save edited ``repo-stats.php`` to your server
8. Once a day open ``<your-server-ip>/repo-stats.php?code=<your-password>`` from the browser to keep your database updated

## Chek differences between two dates
Open phpmyadmin and run these queries. Note that assets that not increased their download count will not be displayed.

- today and first day of tracking

```sql
SELECT a.tag, a.filename, MIN(d.log_date) "previously", MIN(d.dl_count) "were", MAX(d.log_date) "now", MAX(d.dl_count) "are"
FROM project_downloads d INNER JOIN project_asset a
ON d.idasset = a.idasset
GROUP BY d.idasset
HAVING MAX(d.dl_count) - MIN(d.dl_count) > 0
ORDER BY a.tag, MAX(d.dl_count) - MIN(d.dl_count);
```

- today and ``<NUMBER>`` days ago (replace ``<NUMBER>`` with the desired one)

```sql
SELECT a.tag, a.filename, MIN(d.log_date) "previously", MIN(d.dl_count) "were", MAX(d.log_date) "now", MAX(d.dl_count) "are"
FROM project_downloads d INNER JOIN project_asset a
ON d.idasset = a.idasset
WHERE d.log_date in (CURDATE(), DATE_SUB(CURDATE(), interval NUMBER day))
GROUP BY d.idasset
HAVING MAX(d.dl_count) - MIN(d.dl_count) > 0
ORDER BY a.tag, MAX(d.dl_count) - MIN(d.dl_count);
```
