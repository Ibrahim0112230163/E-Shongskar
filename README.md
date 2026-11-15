# E-Shongskar (ই-সংস্কার) — Let's reform together

A community platform for Bangladesh where neighbours in the same area report local problems, organise community work, find blood donors and keep each other safe. Verified local representatives and administrators help move problems toward solutions.

## Setup (XAMPP)

1. Copy this folder into `C:\xampp\htdocs\` (for example `C:\xampp\htdocs\E-Shongskar`).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`), go to the **SQL** tab, paste the contents of `database/e_shongskar.sql` and click **Go**. This creates the `e_shongskar` database.
4. Visit `http://localhost/E-Shongskar/`.

If your MySQL uses a password or a different port, edit `includes/config.php`.

**Default admin:** username `admin`, password `admin123`. Log in at *Log in → Admin* and change the password under *Admin accounts*.

## Features

| Area | What it does |
|---|---|
| Issue reports | Report a problem with a photo; neighbours vote and rate urgency; admins update the status and everyone involved is notified. |
| Community events | Create events, like, comment and join. Organisers can request a fundraiser, which a verified local representative (or an admin) approves before donations open. |
| Event completion | Organiser marks it completed → a verified local representative verifies it → an admin approves it. |
| Blood donors | Register as a donor, search compatible donors in your area or division, and request a phone number. The donor decides whether to share it. |
| Alerts | Admins publish alerts for the whole country, a division or a single area. |
| Emergency | Local, division and national emergency numbers, plus an SOS button that alerts local representatives and admins and can include your location. |
| Messages | Neighbours in the same area can chat when at least one of them is a verified representative. |
| Leaderboard | Points for reports (5), events (4), approved events (6), approved fundraisers (10) and events joined (2); all-time and Citizen of the Month. |
| Admin panel | Dashboard with charts, report management, user verification, admin promotion, event and fundraiser approvals, SOS, alerts, emergency contacts and admin accounts. |

## Project structure

```
index.php, login.php, register.php, logout.php   Public pages
dashboard.php                                    Citizen home
reports.php, report.php, report_new.php          Issue reports
events.php, event.php, event_new.php,
completed_events.php                             Community events
blood.php, alerts.php, emergency.php             Safety
chat.php, notifications.php, neighbours.php,
profile.php, leaderboard.php                     Community & account
admin/                                           Admin panel
includes/                                        Config, database, helpers, layouts
assets/                                          CSS, JS, images, video
uploads/                                         User uploads (avatars, reports, chat)
database/e_shongskar.sql                         Full database script
```
