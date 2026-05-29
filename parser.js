import { chromium } from 'playwright';

(async () => {

    const browser = await chromium.launch({
        headless: true
    });

    const page = await browser.newPage();

    await page.goto('https://www.betexplorer.com/', {
        waitUntil: 'domcontentloaded'
    });

    await page.waitForTimeout(3000);

    const title = await page.title();

    console.log('title:', title);

    await browser.close();

})();
