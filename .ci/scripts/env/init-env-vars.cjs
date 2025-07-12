#!/usr/bin/env node

var fs = require('fs');
const CONFIG = require('../../../.projectconfig.cjs');

// Check for required variables.

let errs = [];
if (!CONFIG.TZ) {
  errs.push("The 'TZ' variable needs to be set in .projectconfig.cjs");
}
if (!CONFIG.TEMP_DIR) {
  errs.push("The 'TEMP_DIR' variable needs to be set in .projectconfig.cjs");
}
if (!CONFIG.CMS_PLATFORM) {
  errs.push("The 'CMS_PLATFORM' variable needs to be set in .projectconfig.cjs");
}
if (!CONFIG.DEFAULT_SITE) {
  errs.push("The 'DEFAULT_SITE' variable needs to be set in .projectconfig.cjs");
}
if (!CONFIG.testingPaths) {
  errs.push("The 'testingPaths' variable needs to be set in .projectconfig.cjs");
}

if (errs.length > 0) {
  console.log(errs.join("\n"));
  process.exit(1);
}



// Save Config as settings.
fs.writeFileSync('.projectconfig.cjson', JSON.stringify(CONFIG, null, 2));
