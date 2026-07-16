# Unraid Pinggy Tunnel Plugin

Manages one persistent SSH tunnel to Pinggy Pro using your single token, with
support for multiple local services forwarded through that one session
(Pinggy's "multiple forwardings" feature — several `-R` rules in one `ssh`
command, since a Pro token only allows one active session at a time).

## What's in `pinggy.plg`

It's fully self-contained — no separate package/tarball to build. Installing
it writes three files and runs a short setup script:

- `/usr/local/emhttp/plugins/pinggy/pinggy.page` — the Settings page (webGUI), listed under **Settings → Tools → Pinggy Tunnel**
- `/usr/local/emhttp/plugins/pinggy/scripts/pinggy-ctl` — start/stop/status/log control script, builds the `ssh` command from your config
- `/etc/rc.d/rc.pinggy` — standard service wrapper around `pinggy-ctl`
- Config lives on the flash drive at `/boot/config/plugins/pinggy/` so it survives reboots:
  - `pinggy.cfg` — token, region/server, reconnect/keepalive/security options, autostart flag
  - `forwardings.cfg` — one line per forwarded service: `enabled|type|hostname|local_host|local_port`

## Installing

**Option A — host it yourself (recommended, and needed for auto-update checks):**
1. Push this repo (or just `pinggy.plg`) to a GitHub repo.
2. Edit the `pluginURL` entity near the top of `pinggy.plg` to point at the raw URL of the file in that repo.
3. On Unraid: **Plugins → Install Plugin**, paste that raw URL, click Install.

**Option B — install locally without hosting anywhere (fine for testing):**
1. Copy `pinggy.plg` to the flash drive, e.g. `/boot/config/plugins/pinggy.plg` (via SMB or SSH).
2. SSH into Unraid and run:
   ```
   plugin install /boot/config/plugins/pinggy.plg
   ```
3. Refresh the Plugins page — it'll show as installed.

**Note on line endings:** if you're editing/pushing from Windows, make sure
Git isn't converting line endings on this repo (CRLF breaks the install
script with a cryptic `run failed ... returned 127`). Add a `.gitattributes`
with `* -text` and set `git config core.autocrlf false` before committing.

## Finding the settings page

It's under **Settings → Tools → Pinggy Tunnel** in the Unraid webGUI — not
under the Plugins tab. Clicking a plugin's icon on the Plugins tab only shows
its info/changelog, the same as it does for every other Unraid plugin; the
Settings tab is always the real entry point for a plugin's configuration UI.

## Using it

1. Go to **Settings → Tools → Pinggy Tunnel**.
2. Paste your Pro token (from `dashboard.pinggy.io`).
3. Pick a **Region** (Auto / USA / Europe / Asia), or fill in **Server
   override** to pin an exact hostname yourself. South America and
   Australia are listed but currently route through Auto — Pinggy hasn't
   published dedicated edge hostnames for those regions.
4. Add a forwarding row. For a straightforward case, leave **Hostname**
   blank and just set local host/port (e.g. `localhost` / `8080`). Blank
   hostname = plain default route, no custom-domain setup needed.
5. If you want multiple named routes through the same token at once (e.g.
   two different subdomains, each pointed at a different local port), that
   requires a wildcard custom domain configured for your token in the
   Pinggy dashboard first — then fill in the Hostname column per row and
   Pinggy will route by SNI/Host header.
6. Save, then **Start Tunnel**. The log panel shows the assigned/persistent
   URL once it connects.

### Toggles

- **Auto Reconnect** — if the SSH session drops, restart it automatically after a few seconds. Off = single connection attempt, no retry.
- **Keep Alive** — send SSH keepalive packets at the configured interval so NAT/firewalls don't silently kill an idle connection.
- **Force New Tunnel** — appends `+force` to the token on connect, telling Pinggy to kill any existing session with that token first. Useful after a reboot where the old tunnel wasn't cleanly closed. Recommended on, since a Pro token only allows one active session.
- **X-Forwarded-For Header** — adds `x:xff`, so Pinggy adds an `X-Forwarded-For` header with the visitor's real IP.
- **Original Request URL Header** — adds `x:fullurl`, so Pinggy adds an `X-Pinggy-Url` header containing the original request URL (useful since Pinggy otherwise rewrites Host/URL info).
- **HTTPS Only** — adds `x:https`, so plain HTTP requests get redirected to HTTPS at the Pinggy edge.

### Forwarding types

`http`, `tcp`, `tls`, `tlstcp`, and a `default` catch-all route are all real
ssh-based Pinggy tunnel types and work as expected. **`udp`** is included in
the dropdown for completeness, but Pinggy's own docs are explicit that UDP
tunnels can only be created through their separate CLI binary — not over a
plain `ssh` session. This plugin doesn't bundle that binary, so any row set
to `udp` is skipped at runtime and logged with a warning rather than failing
silently. If you need UDP tunnels, this would be the natural next feature to
add (bundling/downloading the Pinggy CLI binary as an alternate execution
path).

## Notes / things worth knowing

- The control script is a plain bash reconnect loop (`while true; do ssh ...;
  sleep 3; done` when Auto Reconnect is on), matching Pinggy's own documented
  pattern for long-running tunnels — no extra binaries required beyond the
  `openssh` client already on Unraid.
- Logs are at `/var/log/pinggy.log` (also tailed in the webGUI), capped to
  the last ~2000 lines automatically.
- Everything here assumes Pinggy Pro (persistent domains, single-token
  session, `pro.pinggy.io` / regional `*.a.pinggy.io` hosts). If you ever
  want to test against the free tier, clear the token — sessions will cap
  at 60 minutes and get a random URL each time.

## Testing before wiring it into a real domain

You don't need a real domain pointed at anything to try this — start with a
blank-hostname forwarding to some local port (e.g. a quick `python3 -m
http.server 8080`) and confirm the tunnel comes up and the log shows a
working public URL before touching DNS or your reverse proxy config.
