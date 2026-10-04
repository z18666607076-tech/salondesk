# Deploying SalonDesk

This is a manual deploy for one CentOS machine in mainland China: 4 cores, 4 GB RAM, Docker, and the owner at the keyboard. The production file is `docker-compose.prod.yml`. It publishes port 80 only. MySQL and Redis are not published. Mailpit is not part of this stack.

Stripe Checkout and the live OpenAI assistant are poor defaults from mainland China. Leave `BILLING_DRIVER=fake` and `AI_BOOKING_DRIVER=fake` unless you have already confirmed those networks from this host.

## 1. Install Docker

CentOS 7 is the usual target for this note. CentOS Stream, Rocky Linux, and AlmaLinux use the same Docker CE repo layout with `centos` replaced by the matching distro name when the Aliyun path requires it.

```bash
sudo yum install -y yum-utils
sudo yum-config-manager --add-repo https://mirrors.aliyun.com/docker-ce/linux/centos/docker-ce.repo
sudo yum install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
sudo systemctl enable --now docker
```

Confirm `docker compose version` prints a plugin version. The `docker-compose` Python binary is not required.

## 2. China registry mirrors

Image pulls of `php`, `mysql`, and `redis` go through the Docker daemon. Put this in `/etc/docker/daemon.json` and restart Docker:

```json
{
  "registry-mirrors": [
    "https://docker.m.daocloud.io",
    "https://mirror.ccs.tencentyun.com"
  ],
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}
```

```bash
sudo systemctl restart docker
```

`docker-compose.prod.yml` also points Composer at `https://mirrors.aliyun.com/composer/` while the app image builds. The local `docker-compose.yml` does not.

## 3. firewalld

Open the web ports. Do not open 3306 or 6379.

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
sudo firewall-cmd --list-all
```

Compose already keeps MySQL and Redis off the host interfaces. The firewall is the second lock.

## 4. SELinux

Leave SELinux enforcing. Do not run `setenforce 0` and do not set `SELINUX=disabled`.

```bash
sudo setsebool -P container_manage_cgroup 1
getenforce
```

The production Compose volumes are mounted with `:z`, which gives the containers a private SELinux label. If a container cannot write `storage/` or the MySQL data directory, relabel that path instead of turning SELinux off:

```bash
sudo chcon -Rt container_file_t /var/lib/docker/volumes/salondesk_mysql_data
```

`container-selinux` is installed with Docker CE. Keep it.

## 5. Memory on a 4 GB host

The production file caps containers so they cannot eat the whole box:

| Service | Limit | Why |
| --- | --- | --- |
| MySQL 8.4 | 1280 MB | `innodb_buffer_pool_size=512M`, performance schema off, 40 connections |
| App | 768 MB | PHP's built-in server with `PHP_CLI_SERVER_WORKERS=2` |
| Queue worker | 256 MB | One process, `notifications` then `default` |
| Redis | 192 MB | `maxmemory 128mb`, no RDB snapshots |

That is about 2.5 GB, which leaves room for the OS. Add swap so a traffic spike does not get the MySQL process killed:

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

There is no Mailpit container in production. Mail uses whatever `MAIL_*` you put in `.env`. `log` is enough for a first boot.

## 6. Boot

On the server, in a checkout of this repository:

```bash
cp .env.example .env
```

Edit `.env`:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=http://your-host`
- `APP_KEY` from `echo "base64:$(openssl rand -base64 32)"`
- `DB_PASSWORD` to a long random value (Compose reads it for MySQL as well)
- `DB_HOST` can stay `127.0.0.1` in the file; Compose overrides it to `mysql` for the containers

`SEED_DEMO` defaults to true in the image entrypoint and is set to `false` by the production Compose file, so the demo salons and the password `password` are not loaded. Set `SEED_DEMO=true` only for a private walkthrough, then change those passwords or turn seeding back off and restart.

```bash
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml ps
```

The app listens on port 80. Health check: `curl -fsS http://127.0.0.1/up`.

Create the first salon from the app container after migrate has finished:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan tinker
```

The local Compose file remains the one for development, including Mailpit and published database ports. Do not point it at this server.
