# External Folder Refresh

External Folder Refresh adds a refresh button to the Files interface.

The button refreshes the currently opened folder using a shallow scan and does not recursively scan subfolders. It is intended for external storages where remote changes are not immediately visible in the Files interface.

## Features

- Adds a refresh button to the Files interface.
- Refreshes only the current folder.
- Uses shallow scanning, without scanning subfolders.
- Restricts refresh actions to configured folder prefixes.
- Default allowed folder prefix: `/Yandex`.
- Provides per-user and per-folder cooldowns.
- Provides a scan timeout.
- Provides an admin settings section.

## Default behavior

- App ID: `external_folder_refresh`
- Default allowed folder prefix: `/Yandex`
- Scan mode: shallow scan only
- Backend command: `php occ files:scan --path="/USER/files/CURRENT_FOLDER" --shallow --quiet --no-interaction`
- User cooldown: 30 seconds
- Folder cooldown: 60 seconds
- Scan timeout: 120 seconds

## Security model

The app validates the requested folder path, blocks root scans, restricts scanning to configured folder prefixes, applies per-user and per-folder rate limits, and uses a lock file to prevent parallel scans of the same folder.

## Repository

https://github.com/iamhawk/external_folder_refresh

## License

AGPL-3.0-or-later.
