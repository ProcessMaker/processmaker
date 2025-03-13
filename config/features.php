<?php

/**
 * Add your feature flags here and set them to true.
 *
 * The Environment Variable name should be FEATURE_<name>
 *
 * Make sure to add the feature flag to the release checklist so CloudOps
 * can disable it until it's ready.
 *
 * Use feature flags to control the visibility of new features. For example
 *
 *   if (config('features.dms')) {
 *     // This is the new feature
 *   }
 */

return [
    'dms' => (bool) env('FEATURE_DMS', true),
];
