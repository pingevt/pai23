
const CONFIG = require('../../.projectconfig.cjs');
const TEST_URLS_DATA = CONFIG.testingPaths;

const args = require('minimist')(process.argv.slice(2));

// Stash the directory where the script was started from
var rootPath = process.cwd();

// console.log(process.env);

if (!process.env.TEST_SITE_URL) {
  process.env.TEST_SITE_URL = "http://live-d8train.pantheonsite.io/";
  console.error("ERROR: using local fallback.");
}
if (!process.env.MULTIDEV_SITE_URL) {
  process.env.MULTIDEV_SITE_URL = "http://default-md-d8train.pantheonsite.io/";
  console.error("ERROR: using local fallback.");
}
if (!process.env.TERMINUS_SITE) {
  process.env.TERMINUS_SITE = "d8train";
  console.error("ERROR: using local fallback.");
}

// Stash live URL, removing any trailing slash
var liveURL = process.env.TEST_SITE_URL.replace(/\/$/, "");

// Stash multidev URL, removing any trailing slash
var multidevURL = process.env.MULTIDEV_SITE_URL.replace(/\/$/, "");


global.isRelativeURL = function (url) {
  return !url.startsWith('http');
};


// HTML validator config.
if ((Array.isArray(args.incTest) && args.incTest.includes("html")) || args.incTest == "html") {
  pa11yConfigBuilder = require('./html.config.js');
  pa11yConfigBuilder.build(TEST_URLS_DATA, liveURL, multidevURL);
}

// Backstopjs config.
if ((Array.isArray(args.incTest) && args.incTest.includes("backstopjs")) || args.incTest == "backstopjs") {

  // Check if we explicitly set the env for testing against.
  let refURL = liveURL;

  if (CONFIG.backstopjsReferenceEnv && ["dev", "test", "live"].includes(CONFIG.backstopjsReferenceEnv)) {
    refURL = 'https://' + CONFIG.backstopjsReferenceEnv + '-' + process.env.TERMINUS_SITE + '.pantheonsite.io';
  }

  let backstopjsConfigBuilder = require('./backstopjs.config.js');
  backstopjsConfigBuilder.build(TEST_URLS_DATA, refURL, multidevURL);
}

// Pa11y config.
if ((Array.isArray(args.incTest) && args.incTest.includes("pa11y")) || args.incTest == "pa11y") {
  pa11yConfigBuilder = require('./pa11y.config.js');
  pa11yConfigBuilder.build(TEST_URLS_DATA, liveURL, multidevURL);
}

// WebPageTest validator config.
if ((Array.isArray(args.incTest) && args.incTest.includes("wpt")) || args.incTest == "wpt") {
  wptConfigBuilder = require('./wpt.config.js');
  wptConfigBuilder.build(TEST_URLS_DATA, liveURL, multidevURL);
}
