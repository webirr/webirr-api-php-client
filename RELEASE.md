# PHP SDK Release Procedure

This repo publishes the Composer package `webirr/webirr` through Packagist.
Stable Composer releases are driven by Git tags.

## Release 2.2.0

Current published Packagist version before this release: `2.1.3`.

Planned release version: `2.2.0`.

Package page: `https://packagist.org/packages/webirr/webirr`

## Pre-Release Checks

Run these from the PHP SDK repo root:

```bash
composer install
composer validate
composer audit
find src tests examples -name '*.php' -print0 | xargs -0 -n1 php -l
composer test
WEBIRR_TEST_ENV_MERCHANT_ID="$WEBIRR_TEST_ENV_MERCHANT_ID" \
WEBIRR_TEST_ENV_API_KEY="$WEBIRR_TEST_ENV_API_KEY" \
composer test:testenv
```

Do not commit merchant API keys, Packagist tokens, webhook secrets, real payment
codes, or production payloads.

## Git Release Steps

Use the existing local git identity. Do not override `git config user.name` or
`git config user.email`.

```bash
git status --short
git add README.md RELEASE.md RELEASE_NOTES.md composer.json composer.lock src tests examples
git commit -m "Prepare PHP SDK release 2.2.0"
git tag v2.2.0
git push origin main
git push origin v2.2.0
```

Do not move or reuse an existing published tag. If a released tag needs a fix,
make a new patch release tag.

## GitHub Release

Create a GitHub Release from the pushed `v2.2.0` tag. This is the public release
page and release-note record; the Git tag remains the Composer/Packagist release
signal.

Using GitHub CLI:

```bash
gh release create v2.2.0 \
  --repo webirr/webirr-api-php-client \
  --title "2.2.0" \
  --notes "http client injection" \
  --verify-tag
```

Or use GitHub web UI:

1. Open `https://github.com/webirr/webirr-api-php-client/releases/new`.
2. Choose the existing `v2.2.0` tag.
3. Use title `2.2.0`.
4. Use brief notes such as `http client injection`.
5. Publish the release.

## Packagist Verification

Packagist is expected to auto-update from the pushed Git tag. Wait a short
period after pushing, then check the package metadata:

```bash
composer show webirr/webirr --all
```

Expected result: `versions` includes `2.2.0`, and the latest stable source/dist
points to the `v2.2.0` commit.

If Packagist does not refresh automatically, sign in with the approved WeBirr
Packagist maintainer account and trigger a manual update. Do not store or copy
Packagist credentials in this repo.

Verify installation from a clean Composer project:

```bash
tmpdir="$(mktemp -d)"
cd "$tmpdir"
composer init --no-interaction --name webirr/release-check
composer require webirr/webirr:^2.2
composer show webirr/webirr
php -r 'require "vendor/autoload.php"; $client = new WeBirr\WeBirrClient("merchant", "api-key", true); echo get_class($client), PHP_EOL;'
```

Expected result: Composer installs `webirr/webirr` version `2.2.0`, and the PHP
smoke command prints `WeBirr\WeBirrClient`.
