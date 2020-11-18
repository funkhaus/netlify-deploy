# Netlify Deploy

Trigger a deploy to Netlify either automatically or with a button in the dashboard.

## Setup

1.  Under `Settings > Netlify Deploy` you'll need to config the following:
    1.       A "Build hook" URL found in the Netlify admin under `Site Settings > Build & Deploy > Build hooks` settings page. Recommended to name this hook "WordPress" but it's optional.
    1.  The "Deploy status badge" URL found in the Netlify admin under `Site Settings > General > Deploy status badge` settings page. Note, you only need to enter the URL, not the full badge markdown code.

## Auto Deploy

The "Auto deploy on publish" setting will trigger a deploy whenever the following occurs:

    1. A post/page/CPT is created or updated,
    1. Attachment updated
    1. Menu item changes
    1. Nested Pages plugin changes page order
