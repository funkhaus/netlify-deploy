# Netlify Deploy

Trigger a deploy to Netlify or Sevalla either automatically or with a button in the dashboard.

## Setup

Under `Settings > Netlify Deploy` (or `Sevalla Deploy`), pick the "Frontend host" and save. Then:

### Netlify

1. A "Build hook" URL found in the Netlify admin under `Site Settings > Build & Deploy > Build hooks` settings page. Recommended to name this hook "WordPress" but it's optional.
1. The "Deploy status badge" URL found in the Netlify admin under `Site Settings > General > Deploy status badge` settings page. Note, you only need to enter the URL, not the full badge markdown code.

### Sevalla

Sevalla static sites have no build hook URL, so the plugin uses the Sevalla API.

1. The static site's ID, from its URL in the Sevalla dashboard or `GET /v3/static-sites`.
1. An API key in `wp-config.php`:
    ```php
    define("ND_SEVALLA_API_KEY", "…");
    ```
    Create it under `Integration > API keys` with custom capabilities `STATIC_SITE:READ` and `STATIC_SITE:UPDATE`, both scoped to this one static site. There's no deploy-only permission, so this is the narrowest key that can deploy. Keep one key per site so each can be revoked on its own. The key is never stored in the database or shown in the admin.

Deploys build the static site's default branch. The admin bar shows the latest deployment, including ones started by a git push or from the Sevalla dashboard. It keeps showing "Deploying" for 90 seconds after Sevalla reports success, because the edge takes about a minute to serve the new build. Status is cached for 10 seconds while building and 60 seconds otherwise, to stay under Sevalla's 120 requests/minute company-wide API limit.

Switching hosts keeps the other host's settings, so you can switch back.

## Manual Deploy

You can trigger a manual deploy by clicking the deploy button in the admin bar at the top of the WordPress dashboard. It updates in real time to show current build status. It will ask for confirmation if the user tries to schedule multiple builds.

## Auto Deploy

The "Auto deploy on publish" setting will trigger a deploy whenever the following occurs:

    1. A post/page/CPT is created or updated
    1. Attachment updated (but not created)
    1. Menu item changes
    1. Nested Pages plugin changes the page (or CPT) order
    1. A post/page/CPT is deleted

The auto-deploy function is debounced to only allow 1 deploy every 60 seconds. This means if you try to do multiple deploys, it will only do the first and last, and 60 seconds apart.

## TODO

1. Add a "settings" link on the plugin install page
