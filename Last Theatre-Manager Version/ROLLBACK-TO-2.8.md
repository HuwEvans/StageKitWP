# Rollback Guide: 3.1 -> 2.8

This repository keeps a packaged 2.8 release file: `Theatre-Manager-2.8.zip`.

## Recommended Rollback Steps

1. Create a full WordPress backup (database + `wp-content/uploads` + plugin folder).
2. In WordPress Admin, deactivate the current Theatre Manager plugin.
3. Remove or rename the current plugin folder:
   - `wp-content/plugins/Theatre-Manager`
4. Install `Theatre-Manager-2.8.zip` from this repo root.
5. Activate Theatre Manager.
6. Validate key pages:
   - Seasons, Shows, Cast, Awards admin lists
   - Frontend pages using theatre shortcodes

## Data Notes

- Rolling back code does not automatically remove data created in 3.1.
- Awards data remains in the database as `award` posts and award meta.
- The 2.8 plugin should continue operating with existing Season/Show/Cast data.

## Optional Package Verification

Run this in the repo root to verify package contents before rollback:

```powershell
Expand-Archive -Path .\Theatre-Manager-2.8.zip -DestinationPath .\_tmp_tm_28 -Force
```

After inspection:

```powershell
Remove-Item -Path .\_tmp_tm_28 -Recurse -Force
```
