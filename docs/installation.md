# Installation checklist

- Back up MythicalDash before installing.
- Copy this folder to `storage/addons/idlestop/`.
- Copy `idle-config.example.json` to `idle-config.json`.
- Configure the panel URL and a private Client API key.
- Set file permissions so the API key is not world-readable.
- Leave `dry_run: true` during initial testing.
- Check cron output and validate server eligibility.
- Only then consider setting `dry_run: false`.
- Do not restart Pterodactyl, Wings, or unrelated services for this add-on.
