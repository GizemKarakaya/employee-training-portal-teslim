# Oracle Cloud Always Free deployment preparation

This runbook prepares the two independent Laravel applications for the already-provisioned VM described by the operator: Ubuntu 24.04.5 LTS ARM64, 1 OCPU, 6 GB RAM, public IPv4 `92.5.64.118`, SSH account `ubuntu`. It does not access Oracle Cloud, create cloud resources, alter local `.env` files, or reset databases. The deployment account described below is a separate, restricted non-root account.

## Architecture and cost boundary

- One existing Ubuntu 24.04 ARM64 VM (1 OCPU, 6 GB RAM).
- PHP 8.3-FPM, Caddy, Composer, Node.js 22 for Vite 8 builds, and SQLite.
- Independent Laravel roots and SQLite files for Personnel Service and Training Portal.
- Persistent database files under `/var/lib/ardic`, outside both public document roots and the Git checkout.
- Caddy serves each app directly from its own Laravel `public` directory and obtains TLS certificates automatically.
- No paid database, extra worker, Docker, Redis, or public `artisan serve` process.

The supplied VM size appears within the commonly published Always Free A1 monthly allowance, but confirm actual account allocation, boot volume, region and remaining quota in the Oracle console/billing view. This repository cannot attest to account-level eligibility or charges.

## Hostnames and HTTPS

For a no-purchase demo hostname, [sslip.io](https://sslip.io/) resolves names containing the VM's public IPv4 address to that address and documents use with Let's Encrypt HTTP-01 certificates. The configured names are:

- `personnel.92-5-64-118.sslip.io`
- `training.92-5-64-118.sslip.io`

Caddy's [PHP-FPM handler](https://caddyserver.com/docs/caddyfile/directives/php_fastcgi) routes requests directly to the local `/run/php/php8.3-fpm.sock`, and automatic HTTPS requests certificates for each configured hostname. This requires working public DNS resolution and inbound TCP ports 80 and 443 to reach the VM. A free third-party DNS service is not a permanent SLA; verify its current terms and resolution at deployment time.

## Supported Ubuntu ARM64 package set

Use Ubuntu 24.04 ARM64 repositories for the PHP 8.3 packages below. Confirm `dpkg --print-architecture` reports `arm64`.

```bash
sudo apt update
sudo apt install -y ca-certificates curl git sqlite3 unzip \
  dnsutils ufw \
  php8.3-cli php8.3-fpm php8.3-curl php8.3-mbstring \
  php8.3-sqlite3 php8.3-xml php8.3-zip
```

Install Composer 2 using the Composer project's official installer and verify its published installer checksum before running it. Both applications set Composer's `config.platform.php` to `8.3.6`; their lock files are therefore resolved for the server's PHP 8.3 runtime and select PHP-compatible Symfony 7.4 dependencies. Deploy with `composer install --no-dev` from the lock file; do not run `composer update` on the server. When changing dependencies, regenerate and validate both lock files under the declared platform, then test on PHP 8.3 before deploying. Install Caddy from its official Debian/Ubuntu repository, following Caddy's current ARM64 instructions. Install Node.js 22 ARM64 from a trusted source (or build the Vite assets on a trusted build machine and transfer only the generated `public/build` assets); Ubuntu 24.04's default Node package may be too old for Vite 8. Verify `dpkg --print-architecture`, `php -v`, `php -m`, `composer --version`, `node --version`, and `npm --version`. The setup script rejects PHP outside Laravel's `^8.3` range and Node versions outside Vite's locked engine range (20.19+ or 22.12+).

## Oracle and Ubuntu network firewall

Configure both Oracle VCN and the VM firewall. Oracle's Security List is attached to the public subnet (`public subnet-ardic-demo-vcn`); if the VM's VNIC uses a Network Security Group instead, apply equivalent rules there and avoid maintaining conflicting duplicate policies.

1. In Oracle Cloud Console, open **Networking → Virtual Cloud Networks → ardic-demo-vcn → Security Lists → the list used by the public subnet → Ingress Rules**. Add stateful TCP ingress rules:
   - Source `0.0.0.0/0`, destination port `80` (HTTP, needed for HTTP-01 validation and redirect to HTTPS).
   - Source `0.0.0.0/0`, destination port `443` (HTTPS).
   - SSH port `22`: restrict source to the administrator's current public IP as `/32` where possible. Do not open it to the world unless temporarily unavoidable; remove any broad SSH rule after testing restricted access.
   - Do not add public ingress for `8000`, `8001`, PHP-FPM, or SQLite.
2. Before enabling UFW, preserve the current SSH session and install an allow rule for the actual administrator public IP. From the admin workstation, determine that IP without accessing any secrets, then on the VM use the real value in place of `ADMIN_PUBLIC_IP`:

   ```bash
   sudo ufw allow from ADMIN_PUBLIC_IP/32 to any port 22 proto tcp
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw default deny incoming
   sudo ufw default allow outgoing
   sudo ufw status verbose
   ```

   Keep the existing SSH session open. From a second terminal, open a fresh SSH connection before enabling UFW or removing any pre-existing broad OpenSSH rule. If the second connection fails, do not enable UFW or remove the working rule; correct the source address or Oracle rule first. Once the second connection succeeds, run:

   ```bash
   sudo ufw enable
   sudo ufw status numbered
   ```

   Confirm SSH, 80 and 443 are allowed and 8000/8001 are not. Existing VM firewall policies may differ; inspect before changing them. Do not flush firewall rules.

## First-time VM setup

1. Connect to the existing instance as `ubuntu`. Check the OS/architecture and confirm boot-volume/quota and current account billing state in Oracle Console. Do not create another instance:

   ```bash
   . /etc/os-release
   printf '%s\n' "$PRETTY_NAME"
   dpkg --print-architecture
   ```

   Confirm Ubuntu 24.04 and `arm64` before installing packages.
2. Configure Oracle ingress rules and then UFW using the **Oracle and Ubuntu network firewall** section above. Keep the current SSH session and verify a second SSH connection before enabling UFW. Do not expose ports 8000/8001.
3. Install the system packages above, Composer 2, Node.js 22, and Caddy. Do not start Caddy with a placeholder hostname or before the production files and configuration are ready.
4. Create a dedicated account named `portal-deploy`. This account owns the checkout and backup directory; group membership allows it to read private environment files and write shared SQLite/runtime paths:

   ```bash
   sudo adduser --disabled-password --gecos "" portal-deploy
   sudo usermod -aG www-data portal-deploy
   ```

   Start a fresh login session as `portal-deploy` so its active supplementary groups include `www-data`; the deployment script verifies the active group.
5. Create an empty, deployment-owned checkout path and clone the exact repository and desired reviewed commit as `portal-deploy`; do not use a floating or unknown branch state for a release:

   ```bash
   sudo install -d -o portal-deploy -g www-data -m 2755 /srv/employee-training-portal
   sudo -u portal-deploy git clone --branch main \
     https://github.com/GizemKarakaya/employee-training-portal-teslim.git \
     /srv/employee-training-portal
   ```

   Verify and check out the intended reviewed commit. The deployment account must own the checkout to update it without sudo. Apply setgid to checkout directories so nested files inherit group `www-data`:

   ```bash
   sudo chgrp -R www-data /srv/employee-training-portal
   sudo find /srv/employee-training-portal -type d -exec chmod 2755 {} +
   sudo find /srv/employee-training-portal -type f -exec chmod 0644 {} +
   sudo chown -R portal-deploy:www-data \
     /srv/employee-training-portal/personnel-service/storage \
     /srv/employee-training-portal/personnel-service/bootstrap/cache \
     /srv/employee-training-portal/training-portal/storage \
     /srv/employee-training-portal/training-portal/bootstrap/cache
   sudo find /srv/employee-training-portal/personnel-service/storage \
     /srv/employee-training-portal/personnel-service/bootstrap/cache \
     /srv/employee-training-portal/training-portal/storage \
     /srv/employee-training-portal/training-portal/bootstrap/cache \
     -type d -exec chmod 2770 {} +
   sudo find /srv/employee-training-portal/personnel-service/storage \
     /srv/employee-training-portal/personnel-service/bootstrap/cache \
     /srv/employee-training-portal/training-portal/storage \
     /srv/employee-training-portal/training-portal/bootstrap/cache \
     -type f -exec chmod 0660 {} +
   sudo chmod 0755 /srv/employee-training-portal/deploy/oracle-cloud/setup.sh
   ```

   Checkout and app directories must remain group `www-data`, mode `2755` (setgid plus world read/traverse). This allows Caddy to read public assets while new Composer files inherit group `www-data`. Keep `.env`, `storage`, and `bootstrap/cache` inaccessible to Caddy; runtime directories are group `www-data`, mode `2770`. The deployment script uses umask `0007` for Composer and Artisan, normalizes Composer vendor ownership/modes for PHP-FPM access, and uses `0022` for Vite output, then sets built public assets to directories `0755` and files `0644`.
6. Create the persistent data and private backup paths. Parent data directories are searchable but not writable by the deployment user; only each database directory is setgid/group-writable. Both the deployment user and PHP-FPM (`www-data`) can use the database and SQLite sidecar files:

   ```bash
   sudo install -d -o www-data -g www-data -m 2750 /var/lib/ardic
   sudo install -d -o www-data -g www-data -m 2750 /var/lib/ardic/personnel-service
   sudo install -d -o www-data -g www-data -m 2770 /var/lib/ardic/personnel-service/database
   sudo install -d -o www-data -g www-data -m 2750 /var/lib/ardic/training-portal
   sudo install -d -o www-data -g www-data -m 2770 /var/lib/ardic/training-portal/database
   sudo touch /var/lib/ardic/personnel-service/database/database.sqlite \
     /var/lib/ardic/training-portal/database/database.sqlite
   sudo chown www-data:www-data \
     /var/lib/ardic/personnel-service/database/database.sqlite \
     /var/lib/ardic/training-portal/database/database.sqlite
   sudo chmod 0660 \
     /var/lib/ardic/personnel-service/database/database.sqlite \
     /var/lib/ardic/training-portal/database/database.sqlite
   sudo install -d -o portal-deploy -g portal-deploy -m 0700 /var/backups/ardic
   ```

   `touch` does not truncate an existing SQLite file. Do not use `install`, shell redirection, or a copy operation over a database that already exists. Verify existing database ownership and mode instead. Parent directories `/var/lib/ardic` and each app directory are `www-data:www-data` mode `2750`; database directories are `www-data:www-data` mode `2770` and database files `www-data:www-data` mode `0660`. The backup directory is owned only by `portal-deploy`, mode `0700`; the deployment script creates and verifies backups with mode `0600`.
7. As `portal-deploy`, create each `.env` initially private to that account, then edit it with the real hostname and shared token. Generate a different `APP_KEY` for each app while the deployment user still owns its `.env`. Use `openssl rand -hex 32` in a private terminal for the shared token; never paste it into Git, this runbook, shell history, or a public issue. Use `umask 0007` for Artisan commands that write shared runtime/database files. After generating keys and finishing edits, set `.env` to `root:www-data`, mode `0640`. The deployment user can read it as a trusted member of `www-data`; PHP-FPM can also read it, while the unrelated Caddy user and other users cannot:

   ```bash
   cd /srv/employee-training-portal/personnel-service
   umask 0077
   cp .env.production.example .env
   # Edit .env privately; do not put real values in command arguments.
   (umask 0007; composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader)
   (umask 0007; php artisan key:generate)
   sudo chown root:www-data .env
   sudo chmod 0640 .env

   cd /srv/employee-training-portal/training-portal
   umask 0077
   cp .env.production.example .env
   # Edit .env privately; do not put real values in command arguments.
   (umask 0007; composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader)
   (umask 0007; php artisan key:generate)
   sudo chown root:www-data .env
   sudo chmod 0640 .env
   ```

   The deployer's `www-data` group membership is what keeps these root-owned files readable to future non-root Artisan and Composer commands; verify with `id` and `test -r .env` without printing the file.
8. Set both production `APP_URL` values to their final HTTPS names; set the portal `PERSONNEL_SERVICE_URL` to the Personnel Service HTTPS name. Keep `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, and `SESSION_SAME_SITE=lax`. Both database paths must be the independent absolute paths under `/var/lib/ardic`.
9. Configure PHP-FPM to create group-writable runtime files and let Caddy connect only to its local FastCGI socket. Keep workers running as `www-data`. Install this systemd drop-in for PHP-FPM:

   ```bash
   sudo install -d -m 0755 /etc/systemd/system/php8.3-fpm.service.d
   printf '[Service]\nUMask=0007\n' | sudo tee /etc/systemd/system/php8.3-fpm.service.d/umask.conf >/dev/null
   ```

   In `/etc/php/8.3/fpm/pool.d/www.conf`, configure `listen.owner = www-data`, `listen.group = caddy`, and `listen.mode = 0660`. This grants Caddy access to the socket only, not to `.env`, SQLite, or Laravel runtime directories. Set `APP_ENV=production`, `APP_DEBUG=false`, and `SESSION_SECURE_COOKIE=true`; Laravel then uses the HTTPS forwarded scheme from Caddy and sends secure session cookies only over HTTPS. Install the Caddyfile as `/etc/caddy/Caddyfile`; validate both configurations and enable the services in the next step.

   Caddy's `root` directives point only to each Laravel `public` directory. Keep `.env`, source, `vendor`, and SQLite data outside the document roots. PHP-FPM uses its local Unix socket; there is no public Laravel development server.
10. Run `sudo systemctl daemon-reload` after adding the PHP-FPM drop-in, validate both configs, and enable the services (they start after app setup):

   ```bash
   sudo systemctl daemon-reload
   sudo php-fpm8.3 -t
   sudo caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile
   sudo systemctl enable php8.3-fpm caddy
   ```

   Prepare the backup directory as above; the setup script refuses to run unless it is mode `0700` and owned by the current deployment user. Never store backups under `public/`.
11. From the checkout, run `deploy/oracle-cloud/setup.sh` as `portal-deploy` in a fresh login session with active `www-data` membership. The script checks environment, database, app-root, and Laravel runtime permissions; backs up both databases to unique files with mode `0600`; installs locked Composer/npm dependencies; builds Vite assets; and runs pending migrations only. It never deletes or truncates databases. It uses umask `0007` for Composer/Artisan and normalizes vendor/runtime group access; generated config cache is group-readable by PHP-FPM at mode `0640`. It uses umask `0022` for public Vite assets and explicitly ensures Caddy-readable modes. It does not copy or regenerate `.env`, create administrators, or rerun seeders.
12. On a new database only, seed demo employees/trainings/assignments once with `(umask 0007; php artisan db:seed --force)` in each app as `portal-deploy`. The seeders use `updateOrCreate`, so re-running can overwrite seeded demo rows; never use `migrate:fresh` or rerun seeders on a database with user changes.
13. Create separate admin accounts interactively in each app. Run `(umask 0007; php artisan admin:create)` from each application's directory as `portal-deploy`; it prompts for the password without echoing it and stores a hash. Use unique strong passwords and do not add default credentials or passwords to Git.
14. Restart the managed web services after each release or configuration change:

   ```bash
   sudo systemctl restart php8.3-fpm caddy
   sudo systemctl --no-pager --full status php8.3-fpm caddy
   ```

   There are no separate Laravel queue/worker processes in this app; `QUEUE_CONNECTION=database` is configured but no worker is needed for current synchronous web features.

## Safe update, persistence, backups, and rollback

- Review and check out the intended commit; avoid resetting or cleaning the server checkout. The setup script is not a Git updater.
- Before applying each update, take SQLite online backups. The setup script does this before migrations using the `sqlite3 .backup` command and a unique UTC timestamp. Keep the backup directory private and set a retention policy; backups contain employee/admin/application data.
- The database files remain at `/var/lib/ardic/...` across code updates and service/VM restarts. Do not remove them, replace them with a fresh file, or run `migrate:fresh`.
- `php artisan migrate --force` only applies pending migrations. Review migration code and ensure a fresh backup exists before release.
- To roll back code, check out the previously reviewed commit and rebuild/reinstall locked dependencies, then restart services. Do not automatically roll back migrations. If a migration is incompatible, stop writes, preserve another copy of the current database, and restore the pre-update SQLite backup only after a deliberate data-loss review.
- For a separate manual backup, use SQLite's online backup API while the app is running, e.g. `sqlite3 /var/lib/ardic/personnel-service/database/database.sqlite ".backup '/var/backups/ardic/personnel-manual.sqlite'"`. Repeat for the Portal and copy protected backups off the VM periodically if a second storage location is available without violating the free-cost objective.

## Post-deployment verification

Run these checks from an administrator workstation after DNS/network rules and the initial deployment are complete.

1. Confirm both DNS names resolve to the VM's current public IP. Expected output for each is `92.5.64.118`:

   ```bash
   dig +short personnel.92-5-64-118.sslip.io
   dig +short training.92-5-64-118.sslip.io
   ```

2. On the VM, validate the deployed Caddyfile and PHP-FPM configuration, then check service state:

   ```bash
   sudo caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile
   sudo php-fpm8.3 -t
   sudo systemctl --no-pager --full status php8.3-fpm caddy
   sudo systemctl is-active php8.3-fpm caddy
   sudo systemctl is-enabled php8.3-fpm caddy
   ```

   Both services should be active and enabled. If validation or status fails, inspect `sudo journalctl -u php8.3-fpm -u caddy --since today` before proceeding.

3. Check both HTTPS hosts and their login pages without following redirects or printing application secrets:

   ```bash
   curl --fail --silent --show-error --output /dev/null \
     --write-out 'Personnel HTTPS: %{http_code} %{url_effective}\n' \
     https://personnel.92-5-64-118.sslip.io/login
   curl --fail --silent --show-error --output /dev/null \
     --write-out 'Training HTTPS: %{http_code} %{url_effective}\n' \
     https://training.92-5-64-118.sslip.io/login
   ```

   A successful TLS verification and HTTP `200` response confirms HTTPS routing and the login pages. Do not use `curl -k`, which would bypass certificate validation. Also open both URLs in a browser and confirm the certificates are trusted.

4. Sign in to each application with its separately created administrator account. Verify logout and a CSRF-protected form submission; confirm the session cookie is marked Secure and HttpOnly in browser developer tools.
5. To verify the service-to-service API integration, create or inspect an employee while authenticated in Personnel Service, then open the Training Portal's assignment form. Confirm the employee appears in the selector, create an assignment, and verify its employee details render. The portal's server-side client attaches the shared bearer token; this tests the API path without exposing the token in shell history or terminal output. Confirm the assignment persists after refreshing the portal.
6. Confirm the databases remain separate and persist across service restarts: `/var/lib/ardic/personnel-service/database/database.sqlite` and `/var/lib/ardic/training-portal/database/database.sqlite`. Verify the document roots are only the respective `public` directories and that neither database path can be fetched over HTTPS. Only ports 80/443 should be public; PHP-FPM must remain on its local Unix socket.

## Files prepared in this repository

- [Production deployment runbook](./DEPLOYMENT-ORACLE.md)
- [Caddy configuration template](./deploy/oracle-cloud/Caddyfile)
- [Safe application update script](./deploy/oracle-cloud/setup.sh)
- [Personnel Service production env template](./personnel-service/.env.production.example)
- [Training Portal production env template](./training-portal/.env.production.example)
