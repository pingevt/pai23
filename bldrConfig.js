/**
 * Bldr Config
 *
 * npm i --save-dev @bluecadet/bldr
 *
 * run `bldr dev`, `bldr watch`, or `bldr build`
 *
 * !!------------------------------------------------------------!!
 *
 * `css` and `js` keys can be an object with src and dist paths,
 * or an array of objects with src and dist paths
 *
 * !!------------------------------------------------------------!!
 *
 * `dev.env` can have multiple objects, each with its own set of
 * config for processing. Create an object in `env` and run
 * `bldr dev env=ENV_KEY_NAME` to run specific processing.
 *
 * !!------------------------------------------------------------!!
 *
 */

// Themes
const baseSrcPath = "./web/themes/custom/pai/assets/src";
const baseDestPath = "./web/themes/custom/pai/assets/dist";

// Custom Modules
// const utilityBaseSrcPath = "./web/modules/custom/hgse_utility/assets/src";
// const utilityBaseDestPath = "./web/modules/custom/hgse_utility/assets/dist";

const paiThemeConfig = {
  css: [{
    src: `${baseSrcPath}/css/*.css`,
    dest: `${baseDestPath}/css/`,
    watch: [
      `${baseSrcPath}/**/*.css`,
      `${baseSrcPath}/**/*.scss`,
      `${baseSrcPath}/**/*.pcss`,
    ],
  // },
  // {
  //   src: `${utilityBaseSrcPath}/css/*.css`,
  //   dest: `${utilityBaseDestPath}/css/`,
  //   watch: [
  //     `${utilityBaseSrcPath}/**/*.css`,
  //     `${utilityBaseSrcPath}/**/*.scss`,
  //     `${utilityBaseSrcPath}/**/*.pcss`,
  //   ],
  }],
  js: [{
    src: `${baseSrcPath}/js/*.js`,
    dest: `${baseDestPath}/js/`,
    watch: [`${baseSrcPath}/js/**/*.js`],
  // },
  // {
  //   src: `${utilityBaseSrcPath}/js/*.js`,
  //   dest: `${utilityBaseDestPath}/js/`,
  //   watch: [`${utilityBaseSrcPath}/js/**/*.js`],
  }],
  images: {
    src: `${baseSrcPath}/images/*.{jpg,JPG,jpeg,JPEG,gif,png,svg,ico}`,
    dest: `${baseDestPath}/images/`,
    watch: [`${baseSrcPath}/images/**/*`],
  },
  processSettings: {
    eslint: {
      forceBuildIfError: true
    },
    rollup: {
      outputOptions: {
        format: 'iife',
        sourcemap: true
      }
    }
  }
};

// console.log(paiThemeConfig);

// module.exports = paiThemeConfig;



let drupal_modules = [
  "./web/themes/custom/pai/",
  "./web/themes/custom/pai_admin/",
  "./web/modules/custom/img_processor/",
  "./web/modules/custom/pai_admin_mod/",
  "./web/modules/custom/pai_utility/",
  "./web/modules/custom/pai_quick_edit/",
];

let css_config = [];
let sass_config = [];
let js_config = [];
let images_config = [];

drupal_modules.forEach((val, i) => {
  css_config.push({
    src: val + 'assets/src/css/*.css',
    dest: val + 'assets/dist/css',
    watch: [val + 'assets/src/css/**/*.css'],
  });

  sass_config.push({
    src: val + 'assets/src/scss/*.scss',
    dest: val + 'assets/dist/css',
    watch: [val + 'assets/src/scss/**/*.scss'],
  });

  js_config.push({
    src: val + 'assets/src/js/*.js',
    dest: val + 'assets/dist/js',
    watch: [val + 'assets/src/js/**/*.js'],
  });

  images_config.push({
    src: val + 'assets/src/images/**.{jpg,JPG,jpeg,JPEG,gif,png,svg}',
    dest: val + 'assets/dist/images',
    watch: [val + 'assets/src/images/**/*'],
  });
});

let config = {
  css: css_config,
  // sass: sass_config,
  js: js_config,
  images: images_config,
  // --------------------- END BASIC CONFIG --------------------- //
  processSettings: {
    eslint: {
      forceBuildIfError: true
    },
    rollup: {
      outputOptions: {
        format: 'iife',
        sourcemap: true
      }
    }
  },
  eslint: {
    forceBuildIfError: true
  },
  rollup: {
    outputOptions: {
      format: 'iife',
      sourcemap: true
    }
  }
}

// console.log(config);


// module.exports = paiThemeConfig;
module.exports = config;
