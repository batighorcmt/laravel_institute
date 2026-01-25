# TODO: Fix Testimonial Background Image Issues

## Issues Identified
- Uploaded background image not visible in testimonial settings.
- Printing not working (background not applied during print).

## Changes Made
- Updated background image URL generation in settings view to use `Storage::url()` instead of `asset()`.
- Moved background application from `body` to `.certificate-container` in print CSS for better print compatibility.
- Ensured fallback background for print when no custom background is set.
- Added `opacity: 1` to the certificate container in print styles to ensure the background image is fully visible at 100% opacity.

## Files Modified
- `resources/views/principal/documents/settings/index.blade.php`: Changed img src to use Storage::url.
- `resources/views/principal/documents/testimonial/print.blade.php`: Updated background CSS for print media.

## Next Steps
- Test uploading a background image in testimonial settings.
- Verify the image is visible in the settings preview.
- Test printing a testimonial to ensure background appears in print.
- If issues persist, check APP_URL in .env and ensure storage link is properly set.
