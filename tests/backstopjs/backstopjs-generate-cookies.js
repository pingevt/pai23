#!/usr/bin/env node

const puppeteer = require('puppeteer');
const fsx = require('fs-extra');
const loginUrls = require("./login_urls.json");

fsx.emptyDir('./backstop_data/cookies');

(async () => {
  const browser = await puppeteer.launch();
  const adminPage = await browser.newPage();

  if (!loginUrls || !loginUrls.ref_login_url || !loginUrls.test_login_url) {
    console.log("Previous errors in login URLs");
    console.log(loginUrls);
    await browser.close();
    return;
  }

  // Reference cookies.
  await adminPage.goto(loginUrls.ref_login_url);
  const adminCookies = await adminPage.cookies();

  fsx.writeFileSync('./backstop_data/cookies/cookies_reference.json', JSON.stringify(adminCookies, null, 2));

  // Test Cookies.
  const testPage = await browser.newPage();
  await testPage.goto(loginUrls.test_login_url);
  const testCookies = await testPage.cookies();

  fsx.writeFileSync('./backstop_data/cookies/cookies_test.json', JSON.stringify(testCookies, null, 2));

  // Shut it down.
  await browser.close();
})();
