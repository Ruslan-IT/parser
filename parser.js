import { chromium } from 'playwright';

(async () => {

    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    const url = process.argv[2] || 'https://www.betexplorer.com/football/brazil/serie-b/';



    await page.goto(url, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(3000);



    // Определяем, является ли страница страницей матча (содержит /match/)

    const isMatchPage = url.includes('#ah') || url.includes('/match/');

    if (isMatchPage) {
        // --- Парсинг страницы матча ---
        const leagueElem = await page.locator('li.breadcrumb__li a').last();
        const league = await leagueElem.textContent();

        const urlParts = url.match(/football\/([^\/]+)\/([^\/]+)/);
        const country = urlParts ? urlParts[1] : null;
        const tournament = urlParts ? urlParts[2] : null;

        // Извлекаем ID матча из input#eventID (более надёжно)
        const eventIdInput = await page.locator('input#eventID');
        let matchId = null;
        if (await eventIdInput.count()) {
            matchId = await eventIdInput.getAttribute('data-event-id') || await eventIdInput.getAttribute('value');
        }

        // Названия команд
        const teamTitles = await page.locator('.list-details__item__title').allTextContents();
        const homeTeam = teamTitles.length >= 2 ? teamTitles[0].trim() : null;
        const awayTeam = teamTitles.length >= 2 ? teamTitles[1].trim() : null;

        // Дата (из data-dt)
        const dateElement = await page.locator('.list-details__item__date');
        const dateAttr = await dateElement.getAttribute('data-dt');
        let date = null;
        if (dateAttr) {
            const parts = dateAttr.split(',').map(Number);
            if (parts.length >= 4) {
                const day = parts[0];
                const month = parts[1];
                const year = parts[2];
                const hour = parts[3] || 0;
                const minute = parts[4] || 0;
                date = `${String(day).padStart(2,'0')}.${String(month).padStart(2,'0')}.${year} ${String(hour).padStart(2,'0')}:${String(minute).padStart(2,'0')}`;
            }
        } else {
            date = await dateElement.textContent();
        }

        // Счёт
        const scoreText = await page.locator('.list-details__item__score').textContent();
        let homeScore = null, awayScore = null;
        if (scoreText && scoreText.includes(':')) {
            const parts = scoreText.split(':');
            homeScore = parseInt(parts[0]);
            awayScore = parseInt(parts[1]);
        }

        // --- Сбор AH (переписываем полностью) ---
        let ahData = null;
        if (matchId) {
            try {
                // Переходим на вкладку AH
                const ahUrl = url.includes('#ah') ? url : url + '#ah';
                await page.goto(ahUrl, { waitUntil: 'domcontentloaded' });
                await page.waitForTimeout(2000);

                ahData = await page.evaluate(() => {
                    const tables = document.querySelectorAll('#odds-content .table-main');
                    const lines = [];

                    tables.forEach(table => {


                        const rows = table.querySelectorAll('tbody tr');
                        let targetRow = null;


                        rows.forEach(row => {
                            const handicapCell = row.querySelector('.table-main__doubleparameter');
                            const oddsCells = row.querySelectorAll('.table-main__detail-odds');
                            if (handicapCell && oddsCells.length >= 2) {
                                console.log('Found line:', {
                                    handicap: handicapCell.textContent.trim(),
                                    homeOdds: oddsCells[0].textContent.trim(),
                                    awayOdds: oddsCells[1].textContent.trim()
                                });
                            }
                        });

                        // Ищем bet365
                        for (const row of rows) {
                            const firstCell = row.querySelector('td:first-child');
                            if (firstCell && firstCell.textContent.includes('bet365')) {
                                targetRow = row;
                                break;
                            }
                        }
                        // Если bet365 нет – берём первую строку с коэффициентами
                        if (!targetRow) {
                            for (const row of rows) {
                                const oddsCells = row.querySelectorAll('.table-main__detail-odds');
                                if (oddsCells.length >= 2) {
                                    targetRow = row;
                                    break;
                                }
                            }
                        }
                        if (!targetRow) return;

                        // Извлекаем handicap из этой же строки (а не из thead!)
                        const handicapCell = targetRow.querySelector('.table-main__doubleparameter');
                        if (!handicapCell) return;
                        const handicapText = handicapCell.textContent.trim();
                        if (!handicapText) return;

                        // Парсим handicap
                        let homeHandicap, awayHandicap;
                        const parts = handicapText.split(',').map(s => parseFloat(s.trim()));
                        if (parts.length === 1) {
                            homeHandicap = parts[0];
                            awayHandicap = -parts[0];
                        } else if (parts.length === 2) {
                            const avg = (parts[0] + parts[1]) / 2;
                            homeHandicap = avg;
                            awayHandicap = -avg;
                        } else {
                            return;
                        }


                        // Проверка: индексы должны быть кратны 0.5 (целые форы)
                        const isHalfIndex = (num) => Math.abs(num % 0.5) < 0.001;
                        if (!isHalfIndex(homeHandicap) || !isHalfIndex(awayHandicap)) {
                            return; // пропускаем дробные индексы (0.25, 0.75, 1.25 и т.д.)
                        }

                        // Извлекаем коэффициенты из targetRow
                        const oddsCells = targetRow.querySelectorAll('.table-main__detail-odds');
                        let homeOdds = null, awayOdds = null;
                        if (oddsCells.length >= 2) {
                            homeOdds = parseFloat(oddsCells[0].textContent.trim());
                            awayOdds = parseFloat(oddsCells[1].textContent.trim());
                        }
                        if (homeOdds === null || awayOdds === null || isNaN(homeOdds) || isNaN(awayOdds)) return;

                        lines.push({ homeHandicap, awayHandicap, homeOdds, awayOdds });

                        console.log('LINE:', {
                            homeHandicap,
                            awayHandicap,
                            homeOdds,
                            awayOdds
                        });

                    });

                    if (lines.length === 0) return null;

                    // Определяем balanced и purchase (как было)
                    let balanced = null;
                    let minDiff = Infinity;
                    let bestScore = Infinity;

                    for (const line of lines) {

                        const homeOdds = line.homeOdds;
                        const awayOdds = line.awayOdds;

                        // Отбрасываем мусорные коэффициенты
                        if (
                            homeOdds < 1.20 ||
                            awayOdds < 1.20 ||
                            homeOdds > 10 ||
                            awayOdds > 10
                        ) {
                            continue;
                        }

                        const diff = Math.abs(homeOdds - awayOdds);

                        const score =
                            diff +
                            Math.abs(homeOdds - 2.0) +
                            Math.abs(awayOdds - 2.0);

                        console.log('BALANCED CHECK:', {
                            handicap: `${line.homeHandicap}/${line.awayHandicap}`,
                            homeOdds,
                            awayOdds,
                            diff,
                            score
                        });


                        if (score < bestScore) {
                            bestScore = score;
                            balanced = line;

                            console.log('NEW BALANCED:', {
                                handicap: `${line.homeHandicap}/${line.awayHandicap}`,
                                homeOdds,
                                awayOdds,
                                score
                            });
                        }
                    }


                    let purchase = null;
                    if (balanced) {
                        const targetHome = balanced.homeHandicap + 0.5;
                        const targetAway = -targetHome; // симметричная фора для гостей
                        for (const line of lines) {
                            if (Math.abs(line.homeHandicap - targetHome) < 0.01 && Math.abs(line.awayHandicap - targetAway) < 0.01) {
                                purchase = line;
                                break;
                            }
                        }
                        if (!purchase) {
                            let minDist = Infinity;
                            for (const line of lines) {
                                const dist = Math.abs(line.homeHandicap - targetHome) + Math.abs(line.awayHandicap - targetAway);
                                if (dist < minDist) {
                                    minDist = dist;
                                    purchase = line;
                                }
                            }
                        }
                    }

                    return { balanced, purchase, allLines: lines  };
                });



            } catch (e) {
                console.warn('Ошибка сбора AH:', e.message);
            }
        }

        // Собираем результат
        const matches = [{
            league: league?.trim(),
            country: country,
            tournament: tournament,
            date: date,
            homeTeam: homeTeam,
            awayTeam: awayTeam,
            oddHome: null,  // не парсим 1X2 на странице матча (они уже есть из турнирной страницы)
            oddDraw: null,
            oddAway: null,
            homeScore: homeScore,
            awayScore: awayScore,
            matchId: matchId,
            ah: ahData,
            fullUrl: url.split('#')[0],
        }];

        console.log(JSON.stringify(matches));
        await browser.close();
        return;
    }

    // --- Парсинг страницы турнира ---
    // Извлекаем лигу, страну, турнир из URL
    const urlParts = url.match(/football\/([^\/]+)\/([^\/]+)/);
    const country = urlParts ? urlParts[1] : null;
    const tournament = urlParts ? urlParts[2] : null;

    // Хлебные крошки для лиги (последний элемент)
    const leagueElem = await page.locator('li.breadcrumb__li a').last();
    const league = await leagueElem.textContent();

    const matches = [];

    // 1. Предстоящие матчи (Next matches) – таблица с классом table-main--leaguefixtures
    const upcomingRows = await page.locator('.table-main--leaguefixtures tbody tr').all();
    for (const row of upcomingRows) {
        // Ссылка на матч (внутри .in-match)
        const linkElem = row.locator('.in-match');
        if (await linkElem.count() === 0) continue;
        const href = await linkElem.getAttribute('href');

        const fullUrl = href ? `https://www.betexplorer.com${href}` : null;
        const matchId = href ? href.match(/\/([a-zA-Z0-9]+)\/?$/)[1] : null;

        // Названия команд (два span внутри .in-match)
        const teamSpans = await linkElem.locator('span').allTextContents();
        const homeTeam = teamSpans[0]?.trim();
        const awayTeam = teamSpans[1]?.trim();

        // Коэффициенты (кнопки в ячейках .table-main__odds)
        const oddsButtons = await row.locator('.table-main__odds button').allTextContents();



        const oddHome = oddsButtons[0]?.trim() || null;
        const oddDraw = oddsButtons[1]?.trim() || null;
        const oddAway = oddsButtons[2]?.trim() || null;

        

        // Дата – пробуем .table-main__datetime (для Fixtures), если нет – .h-text-right (для Results)
        let date = null;
        const dateTimeElem = row.locator('.table-main__datetime');
        if (await dateTimeElem.count() > 0) {
            date = await dateTimeElem.textContent();
        } else {
            const dateText = await row.locator('.h-text-right').textContent();
            date = dateText?.trim() || null;
        }
        date = date?.trim() || null;


        // Извлечение сезона из URL (для турнирной страницы)
        let season = null;
        const seasonMatch = url.match(/\/(\d{4})\/?$/); // например, /2026/
        if (seasonMatch) {
            season = seasonMatch[1];
        } else {
            // Если год не указан в URL, используем текущий (или можно взять из выпадающего списка)
            // Но для простоты оставим null
        }


        // Счёт для предстоящих матчей отсутствует
        matches.push({
            league: league?.trim(),
            country: country,
            tournament: tournament,
            date: date,
            homeTeam: homeTeam,
            awayTeam: awayTeam,
            oddHome: oddHome,
            oddDraw: oddDraw,
            oddAway: oddAway,
            homeScore: null,
            awayScore: null,
            matchId: matchId,
            fullUrl: fullUrl,
            season: season,
        });
    }

    // 2. Результаты (Results) – таблица .table-main (без класса --leaguefixtures)
    // Внимание: на странице может быть несколько таблиц .table-main, но результаты обычно в одной из них.
    // Используем селектор .table-main:not(.table-main--leaguefixtures) tbody tr
    const resultRows = await page.locator('.table-main:not(.table-main--leaguefixtures) tbody tr').all();
    for (const row of resultRows) {
        // Пропускаем строки-заголовки (содержат th) – проверяем наличие .in-match
        const linkElem = row.locator('.in-match');
        if (await linkElem.count() === 0) continue;

        const href = await linkElem.getAttribute('href');

        const fullUrl = href ? `https://www.betexplorer.com${href}` : null;
        const matchId = href ? href.match(/\/([a-zA-Z0-9]+)\/?$/)[1] : null;

        const teamSpans = await linkElem.locator('span').allTextContents();
        const homeTeam = teamSpans[0]?.trim();
        const awayTeam = teamSpans[1]?.trim();

        // Коэффициенты – ячейки .table-main__odds, но могут быть с обёртками
        // В результатах коэффициенты могут быть выделены (colored), но текст внутри span
        const oddsCells = await row.locator('.table-main__odds').all();
        let oddHome = null, oddDraw = null, oddAway = null;
        if (oddsCells.length >= 3) {
            // Извлекаем текст из каждой ячейки (может быть внутри span)
            oddHome = await oddsCells[0].textContent();
            oddDraw = await oddsCells[1].textContent();
            oddAway = await oddsCells[2].textContent();
            // Очищаем от лишних пробелов
            oddHome = oddHome?.trim() || null;
            oddDraw = oddDraw?.trim() || null;
            oddAway = oddAway?.trim() || null;
        }

        // Счёт – ячейка .h-text-center с ссылкой
        const scoreLink = row.locator('.h-text-center a');
        let homeScore = null, awayScore = null;
        if (await scoreLink.count() > 0) {
            const scoreText = await scoreLink.textContent();
            if (scoreText && scoreText.includes(':')) {
                const parts = scoreText.split(':');
                homeScore = parseInt(parts[0]);
                awayScore = parseInt(parts[1]);
            }
        }

        // Дата – последняя ячейка .h-text-right (может быть 'Yesterday', '16.06.' и т.д.)
        const dateText = await row.locator('.h-text-right').textContent();
        const date = dateText?.trim() || null;

        matches.push({
            league: league?.trim(),
            country: country,
            tournament: tournament,
            date: date,
            homeTeam: homeTeam,
            awayTeam: awayTeam,
            oddHome: oddHome,
            oddDraw: oddDraw,
            oddAway: oddAway,
            homeScore: homeScore,
            awayScore: awayScore,
            matchId: matchId,
            fullUrl: fullUrl,
        });
    }

    console.log(JSON.stringify(matches));
    await browser.close();
})();
