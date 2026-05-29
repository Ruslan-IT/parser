import { chromium } from 'playwright';

(async () => {

    const browser = await chromium.launch({
        headless: false
    });

    const page = await browser.newPage();

    await page.goto('https://www.betexplorer.com/', {
        waitUntil: 'domcontentloaded'
    });

    await page.waitForTimeout(3000);


    const title  = await page.title();

    console.log('title:', title);


    const html = await page.content();

    console.log(html.substring(0, 1000));

    await browser.close();

})();
