#!/usr/bin/env node

module.exports = async (page, scenario, vp, isReference, Engine) => {

  if (scenario.auth)
    await require('./loadCookies')(page, scenario, vp, isReference, Engine);
};
