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
    src: `${baseSrcPath}/images/*.{jpg,JPG,jpeg,JPEG,gif,png,svg}`,
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

module.exports = paiThemeConfig;
