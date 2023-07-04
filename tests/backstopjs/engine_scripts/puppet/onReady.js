#!/usr/bin/env node

module.exports = async (page, scenario, vp, isReference, Engine) => {

  // console.log(page);
  // console.log(scenario);
  // console.log(scenario, vp);

  // await page.setViewport({ width: vp.width, height: vp.height });

  await page.evaluate(() => {
    var event = new Event('forceLoad');
    document.body.dispatchEvent(event);
  });

  if (scenario.filters && scenario.filters.includes("search")) {
    await page.evaluate(() => {
      document.querySelector("button.u-fill-white.c-global-header__search-btn").click();
    });
  }
  if (scenario.filters && scenario.filters.includes("search-fill")) {
    await page.evaluate(() => {
      document.querySelector("#edit-keys").value = "Amon Carter Museum";
    });
  }

  // await page.waitFor(3000);
};
