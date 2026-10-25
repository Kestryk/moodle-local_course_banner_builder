const {
  test,
  chromium
} = require('@playwright/test');

const fs = require('node:fs');

test('CCB production read-only alignment baseline', async () => {
  test.setTimeout(600000);
  const out = process.env.CCB_ALIGNMENT_ARTIFACT_ROOT;
  const result = {
    rows: [],
    errors: []
  };

  const save = () => fs.writeFileSync(out + '/production-before.json', JSON.stringify(result, null, 2));

  const browser = await chromium.launch({
    headless: true,
    executablePath: process.env.CCB_ALIGNMENT_CHROME
  });

  try {
    for (const [site, id] of [['community', 5], ['staging', 2]]) {
      const context = await browser.newContext();
      const page = await context.newPage();
      page.setDefaultTimeout(20000);
      const base = 'https://' + site + '.easyedu.fr';
      page.on('pageerror', e => result.errors.push({
        site,
        url: page.url(),
        message: e.message
      }));
      await page.goto(base + '/login/index.php');
      await page.locator('#username').fill(process.env.EASYEDU_MOODLE_USERNAME);
      await page.locator('#password').fill(process.env.EASYEDU_MOODLE_PASSWORD);
      await Promise.all([page.waitForURL(u => !u.pathname.includes('/login/')), page.locator('#loginbtn').click()]);

      for (const width of [1600, 768]) for (const [name, url] of [['dashboard', '/my/'], ['catalogue', '/course/index.php'], ['course', '/course/view.php?id=' + id], ['student', '/local/groupimport/manage.php?id=' + id], ['settings', '/course/edit.php?id=' + id]]) {
        await page.setViewportSize({
          width,
          height: 1000
        });
        await page.goto(base + url, {
          waitUntil: 'domcontentloaded',
          timeout: 60000
        });
        await page.waitForTimeout(2300);
        const m = await page.evaluate(() => {
          const box = e => {
            if (!e) return null;
            const r = e.getBoundingClientRect();
            return {
              left: r.left,
              right: r.right,
              width: r.width
            };
          };

          const s = document.querySelector('.local-course-banner-builder-native-shell');
          const t = document.querySelector('#local-groupimport-easystud') || document.querySelector('#region-main .block.card') || document.querySelector('#region-main .course_category_tree') || document.querySelector('#region-main .course-content') || document.querySelector('#region-main form.mform');
          return {
            format: s?.getAttribute('data-banner-format'),
            shell: box(s),
            oracle: box(t),
            frame: box(document.querySelector('#region-main'))
          };
        });
        result.rows.push({
          site,
          name,
          width,
          ...m
        });
        save();
        if (width === 1600 && (name === 'student' || name === 'dashboard')) await page.screenshot({
          path: out + '/prod-before-' + site + '-' + name + '.png'
        });
      }

      await context.close();
    }
  } catch (e) {
    result.failure = e.message;
    save();
    throw e;
  } finally {
    save();
    await browser.close();
  }
});
