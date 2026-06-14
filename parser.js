import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    const url = process.argv[2] || 'https://www.betexplorer.com/football/france/ligue-1/angers-lille/lYTqhFje/';

    await page.goto(url, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(3000);

    // Лига (из хлебных крошек)
    const leagueElem = await page.locator('li.breadcrumb__li a').last();
    const league = await leagueElem.textContent();

    // Извлекаем страну и турнир из URL для базы
    const urlParts = url.match(/football\/([^\/]+)\/([^\/]+)/);
    const country = urlParts ? urlParts[1] : null;
    const tournament = urlParts ? urlParts[2] : null;

    const rows = page.locator('.head-to-head__row');
    const count = await rows.count();
    const matches = [];

    for (let i = 0; i < count; i++) {
        const row = rows.nth(i);

        const date = await row.locator('.head-to-head__date .mobileHidden').textContent();
        const homeTeam = await row.locator('.table-main__participantHome p').textContent();
        const awayTeam = await row.locator('.table-main__participantAway p').textContent();
        const odds = await row.locator('.table-main__odd').allTextContents();

        // Счёт
        const scoreParts = await row.locator('.mainResult.mobileHidden span').allTextContents();
        let homeScore = null, awayScore = null;
        if (scoreParts.length >= 3) {
            homeScore = parseInt(scoreParts[0]);
            awayScore = parseInt(scoreParts[2]);
        }

        // Извлекаем ID матча из ссылки на матч (если есть)
        const matchLinkElem = row.locator('.table-main__link').first();
        let matchId = null;
        if (await matchLinkElem.count()) {
            const href = await matchLinkElem.getAttribute('href');
            const idMatch = href.match(/\/([a-zA-Z0-9]+)\/?$/);
            matchId = idMatch ? idMatch[1] : null;
        }

        matches.push({
            league: league?.trim(),
            country: country,
            tournament: tournament,
            date: date?.trim(),
            homeTeam: homeTeam?.trim(),
            awayTeam: awayTeam?.trim(),
            odds: odds.map(o => o.trim()),
            homeScore,
            awayScore,
            matchId, // уникальный ID матча на betexplorer
        });
    }

    console.log(JSON.stringify(matches));
    await browser.close();
})();
