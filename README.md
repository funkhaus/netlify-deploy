# Netlify Deploy

Trigger a deploy to Netlify either automatically or with a button in the dashboard.

## Setup

1.  Under `Settings > Netlify Deploy` you'll need to config the following:
    1. A "Build hook" URL found in the Netlify admin under `Site Settings > Build & Deploy > Build hooks` settings page. Recommended to name this hook "WordPress" but it's optional.
    1. The "Deploy status badge" URL found in the Netlify admin under `Site Settings > General > Deploy status badge` settings page. Note, you only need to enter the URL, not the full badge markdown code.

## Manual Deploy

You can trigger a manual deploy by clicking the deploy button in the admin bar at the top of the WordPress dashboard. It updates in real time to show current build status. It will ask fro confirmation if the user tries to schedule multiple builds.

## Auto Deploy

The "Auto deploy on publish" setting will trigger a deploy to Netlify whenever the following occurs:

    1. A post/page/CPT is created or updated
    1. Attachment updated (but not created)
    1. Menu item changes
    1. Nested Pages plugin changes the page (or CPT) order

The auto-deploy function is debounced to only allow 1 deploy every 60 seconds. This means if you try to do multiple deploys, it will only do the first and last, and 60 seconds apart.
