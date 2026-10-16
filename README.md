# MythicalDash IdleStop

A manually installed MythicalDash add-on that checks Minecraft Java player
counts and can send a Pterodactyl stop signal after a configurable idle period.

## Important safety notes

- A fresh installation must remain in dry-run mode until tested.
- Configure your own panel URL and private Client API key locally.
- Never publish `idle-config.json`, API keys, runtime state, logs, or backups.
- The add-on is intended to consider only non-deleted server IDs registered
  in MythicalDash's `mythicaldash_servers` database table.
- A Pterodactyl API key may have power over servers belonging to other users.
  Keep it private and grant only the permissions your setup requires.
- Unknown Minecraft status must not be treated as zero players.
- Review and test the code against your exact MythicalDash version before
  enabling real stop requests.

## Installation

1. Back up your MythicalDash installation.
2. Copy the `idlestop` folder into
   `storage/addons/idlestop/` in your MythicalDash installation.
3. Create `idle-config.json` by copying `idle-config.example.json`.
4. Edit the local config with your own panel URL and Client API key.
5. Keep `dry_run` set to `true`.
6. Run the MythicalDash cron runner and review its output.
7. Confirm only intended MythicalDash-managed servers are considered.
8. Enable real stop requests only after testing and reviewing the permissions.

Do not copy the example API key into production. Protect the local config file
so only the appropriate system account can read it.

## Default behavior

The example uses a 10-minute idle threshold and dry-run mode. The cron runner
must be configured by the MythicalDash installation to execute add-on cron
jobs. This package does not change cron schedules or restart services.

## Uninstall

Disable the add-on in its local configuration, remove the `idlestop` add-on
directory, and remove any local runtime state after reviewing it. Keep a
backup until you have confirmed the add-on is no longer needed.

## Compatibility

This is an independently maintained community add-on. Test it with your
installed MythicalDash and Pterodactyl versions before production use.
