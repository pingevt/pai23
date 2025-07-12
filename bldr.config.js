import { bldrConfig } from '@bluecadet/bldr/config';

/**
 * Config vars for standard builds
 */
let full_css_config = [];
let full_js_config = [];
let full_sdc_config = {
  directory: [
    // './web/themes/custom/uva/components',
    // './web/modules/custom/uva_utility/components',
  ],
  assetSubDirectory: 'assets',
};

let drupal_modules = [
  './web/themes/custom/pai',
  './web/themes/custom/pai_admin',
  './web/modules/custom/img_processor',
  './web/modules/custom/pai_admin_mod',
  './web/modules/custom/pai_quick_edit',
  './web/modules/custom/pai_redirect',
  './web/modules/custom/pai_utility',
];

let watch_paths = [];

drupal_modules.forEach((val, i) => {
  full_css_config.push({
    src: val + '/assets/src/css/**/*.css',
    dest: val + '/assets/dist/css',
    watch: [val + '/assets/src/css/**/*.css'],
  });

  full_js_config.push({
    src: val + '/assets/src/js/**/*.js',
    dest: val + '/assets/dist/js',
    watch: [val + '/assets/src/js/**/*.js'],
  });

  watch_paths.push(val);
});

export default bldrConfig({
  css: full_css_config,
  js: full_js_config,
  sdc: full_sdc_config,
  watchPaths: watch_paths,
  eslint: {
    useEslint: false,
    options: {},
    forceBuildIfError: true,
  },
  stylelint: {
    useStyleLint: true,
    forceBuildIfError: true,
  },
  rollup: {
    useRollup: true,
    useTerser: false,
    sdcOptions: {
      bundle: true,
      minify: false,
      format: 'es'
    },
  },
  browsersync: {
    disable: true,
    instanceName: null,
  },
});
