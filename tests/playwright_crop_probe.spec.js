const { test } = require('playwright/test');

test('probe local moodle login page', async ({ page }) => {
    const urls = [
        'http://localhost/login/index.php',
        'http://localhost/moodle/login/index.php'
    ];
    for (const url of urls) {
        try {
            const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 15000 });
            console.log(JSON.stringify({
                url,
                ok: true,
                status: response ? response.status() : null,
                title: await page.title()
            }));
        } catch (error) {
            console.log(JSON.stringify({
                url,
                ok: false,
                error: String(error)
            }));
        }
    }
});
