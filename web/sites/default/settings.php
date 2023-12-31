<?php

/**
 * @file
 * Load services definition file.
 */

$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * N.b. The settings.pantheon.php file makes some changes
 *      that affect all environments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to ensure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * Place the config directory outside of the Drupal root.
 */
$settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config';

/**
 * Skipping permissions hardening will make scaffolding
 * work better, but will also raise a warning when you
 * install Drupal.
 *
 * Https://www.drupal.org/project/drupal/issues/3091285
 */
// $settings['skip_permissions_hardening'] = TRUE;

/**
 * If there is a local settings file, then include it.
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

// Config Split Settings.
$config['config_split.config_split.development']['status'] = TRUE;
if (defined('PANTHEON_ENVIRONMENT')) {
  $config['config_split.config_split.development']['folder'] = dirname(DRUPAL_ROOT) . '/config-dev';
  if (in_array($_ENV['PANTHEON_ENVIRONMENT'], ['live', 'test', 'pr-887'])) {
    $config['config_split.config_split.development']['status'] = FALSE;
  }
}
