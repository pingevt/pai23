<?php

/**
 * @file
 * Load services definition file.
 */

use Drupal\Core\Installer\InstallerKernel;

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


// Redirect to https.
if (isset($_SERVER['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli') {
  // Redirect to https://$primary_domain in the Live environment.
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    // Replace www.example.com with your registered domain name.
    $primary_domain = "peteinge.com";
  }
  else {
    // Redirect to HTTPS on every Pantheon environment.
    $primary_domain = $_SERVER['HTTP_HOST'];
  }

  // Force www.
  if ($primary_domain == "www.peteinge.com") {
    $primary_domain = "peteinge.com";
  }

  // Check for redirected domains...
  $doms = [];

  if (in_array($primary_domain, $doms)) {
    $primary_domain = "peteinge.com";
  }

  if ($_SERVER['HTTP_HOST'] != $primary_domain
      || !isset($_SERVER['HTTP_X_SSL'])
      || $_SERVER['HTTP_X_SSL'] != 'ON') {

    // Name transaction "redirect" in New Relic for
    // improved reporting (optional).
    if (extension_loaded('newrelic')) {
      newrelic_name_transaction("redirect");
    }

    header('HTTP/1.0 301 Moved Permanently');
    header('Location: https://' . $primary_domain . $_SERVER['REQUEST_URI']);
    exit();
  }
}

/**
 * Skipping permissions hardening will make scaffolding
 * work better, but will also raise a warning when you
 * install Drupal.
 *
 * Https://www.drupal.org/project/drupal/issues/3091285
 */
// $settings['skip_permissions_hardening'] = TRUE;

// Configure Redis.
// if (defined(
//  'PANTHEON_ENVIRONMENT'
// ) && !InstallerKernel::installationAttempted(
// ) && extension_loaded('redis')) {
//   // Set Redis as the default backend for any cache bin not otherwise specified.
//   $settings['cache']['default'] = 'cache.backend.redis';

//   // Phpredis is built into the Pantheon application container.
//   $settings['redis.connection']['interface'] = 'PhpRedis';

//   // These are dynamic variables handled by Pantheon.
//   $settings['redis.connection']['host'] = $_ENV['CACHE_HOST'];
//   $settings['redis.connection']['port'] = $_ENV['CACHE_PORT'];
//   $settings['redis.connection']['password'] = $_ENV['CACHE_PASSWORD'];

//   $settings['redis_compress_length'] = 100;
//   $settings['redis_compress_level'] = 1;

//   $settings['cache_prefix']['default'] = 'pantheon-redis';

//   // Use the database for forms.
//   $settings['cache']['bins']['form'] = 'cache.backend.database';

//   // Apply changes to the container configuration to make better use of Redis.
//   // This includes using Redis for the lock and flood control systems, as well
//   // as the cache tag checksum. Alternatively, copy the contents of that file
//   // to your project-specific services.yml file, modify as appropriate, and
//   // remove this line.
//   $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

//   // Allow the services to work before the Redis module itself is enabled.
//   $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';

//   // Manually add the classloader path, this is required for the container
//   // cache bin definition below.
//   $class_loader->addPsr4('Drupal\\redis\\', 'modules/contrib/redis/src');

//   // Use redis for container cache.
//   // The container cache is used to load the container definition itself, and
//   // thus any configuration stored in the container itself is not available
//   // yet. These lines force the container cache to use Redis rather than the
//   // default SQL cache.
//   $settings['bootstrap_container_definition'] = [
//     'parameters' => [],
//     'services' => [
//       'redis.factory' => [
//         'class' => 'Drupal\redis\ClientFactory',
//       ],
//       'cache.backend.redis' => [
//         'class' => 'Drupal\redis\Cache\CacheBackendFactory',
//         'arguments' => [
//           '@redis.factory',
//           '@cache_tags_provider.container',
//           '@serialization.phpserialize',
//         ],
//       ],
//       'cache.container' => [
//         'class' => '\Drupal\redis\Cache\PhpRedis',
//         'factory' => ['@cache.backend.redis', 'get'],
//         'arguments' => ['container'],
//       ],
//       'cache_tags_provider.container' => [
//         'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
//         'arguments' => ['@redis.factory'],
//       ],
//       'serialization.phpserialize' => [
//         'class' => 'Drupal\Component\Serialization\PhpSerialize',
//       ],
//     ],
//   ];
// }

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

$config['image.settings']['suppress_itok_output'] = TRUE;
$config['image.settings']['allow_insecure_derivatives'] = TRUE;

// Dropzone settings for moving temp directory:
// https://www.drupal.org/project/dropzonejs/issues/2916330
$config['dropzonejs.settings']['tmp_upload_scheme'] = 'private';

// Automatically generated include for settings managed by ddev.
$ddev_settings = __DIR__ . '/settings.ddev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
  require $ddev_settings;
}
