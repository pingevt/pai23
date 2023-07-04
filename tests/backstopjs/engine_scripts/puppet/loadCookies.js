#!/usr/bin/env node

const fs = require('fs');

module.exports = async (page, scenario, vp, isReference, Engine) => {
  console.log("Baking the cookies!!");
  // console.log(isReference);
  let cookieFile = "";

  if (isReference) {
    cookieFile = './backstop_data/cookies/cookies_reference.json';
  } else {
    cookieFile = './backstop_data/cookies/cookies_test.json';
  }

  // console.log(cookieFile);

  // Check if file exists.
  if (!fs.existsSync(cookieFile)) {
    // If not load cookies.
    console.log("cookies do not exists.");
  }
  else {
    console.log("cookie jar is full.");
  }

  let cookieData = fs.readFileSync(cookieFile);
  let cookies = JSON.parse(cookieData);

  // SET COOKIES
  const setCookies = async () => {
    return Promise.all(
      cookies.map(async (cookie) => {
        console.log(cookie);
        await page.setCookie(cookie);
      })
    );
  };
  await setCookies();
  // console.log('Cookie state restored with:', JSON.stringify(cookies, null, 2));
  console.log('Cookie state restored');

};
