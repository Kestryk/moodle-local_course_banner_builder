// Pair this geometry matrix with the explicit dashboard drawer run and the result validator.
const {
  test,
  expect,
  chromium
} = require('@playwright/test');

const fs = require('node:fs'),
      cp = require('node:child_process');

test('CCB content alignment dynamic final native', async () => {
  test.setTimeout(1600000);
  const out = process.env.CCB_ALIGNMENT_ARTIFACT_ROOT,
        phase = process.env.CCB_ALIGNMENT_PHASE || 'after-final';
  const base = new URL(process.env.EASYEDU_MOODLE_URL).origin;
  const formats = (process.env.CCB_ALIGNMENT_FORMATS || 'standard').split(',');
  const php = process.env.CCB_ALIGNMENT_PHP,
        fixture = out + '/fixture.php';
  const result = {
    phase,
    rows: [],
    errors: []
  };

  const save = () => fs.writeFileSync(out + '/' + phase + '.json', JSON.stringify(result, null, 2));

  const browser = await chromium.launch({
    headless: true,
    executablePath: process.env.CCB_ALIGNMENT_CHROME
  });

  try {
    const page = await browser.newPage({
      viewport: {
        width: 1600,
        height: 1000
      }
    });
    page.setDefaultTimeout(15000);
    page.on('pageerror', e => {
      result.errors.push({
        url: page.url(),
        message: e.message,
        stack: e.stack
      });
      save();
    });
    await page.goto(base + '/login/index.php');
    await page.locator('#username').fill(process.env.EASYEDU_MOODLE_USERNAME);
    await page.locator('#password').fill(process.env.EASYEDU_MOODLE_PASSWORD);
    await Promise.all([page.waitForURL(u => !u.pathname.includes('/login/')), page.locator('#loginbtn').click()]);

    const sample = () => page.evaluate(() => {
      const box = e => {
        const r = e.getBoundingClientRect(),
              s = getComputedStyle(e);
        return {
          tag: e.tagName,
          id: e.id,
          cls: e.className,
          left: r.left,
          right: r.right,
          width: r.width,
          height: r.height,
          bg: s.backgroundColor,
          padding: s.padding,
          position: s.position,
          border: s.borderLeftWidth
        };
      };

      const shell = document.querySelector('.local-course-banner-builder-native-shell');
      const frame = document.querySelector('#region-main');
      const target = document.querySelector('#local-groupimport-easystud') || document.querySelector('#region-main .block.card') || document.querySelector('#region-main .course_category_tree') || document.querySelector('#region-main .course-content') || document.querySelector('#region-main form.mform');
      const candidates = frame ? [...frame.querySelectorAll(':scope > *, [role="main"] > *, .block.card, .course-content, .local-groupimport-easystud__panel')].filter(e => e.getBoundingClientRect().width > 100).slice(0, 18).map(box) : [];
      return {
        oracle: target ? box(target) : null,
        shell: shell ? box(shell) : null,
        frame: frame ? box(frame) : null,
        candidates,
        bodyClass: document.body.className,
        drawers: [...document.querySelectorAll('.drawer')].map(e => ({
          id: e.id,
          open: e.classList.contains('show')
        }))
      };
    });

    for (const format of formats) {
      cp.execFileSync(php, [fixture, 'format', format], {
        timeout: 120000
      });
      cp.execFileSync(php, [process.env.CCB_ALIGNMENT_PURGE, process.env.CCB_ALIGNMENT_CONFIG, '8052'], {
        timeout: 120000
      });

      for (const width of [1600, 768]) for (const [context, url] of [['dashboard', '/my/'], ['catalogue', '/course/index.php'], ['course', '/course/view.php?id=71'], ['student', '/local/groupimport/manage.php?id=71'], ['settings', '/course/edit.php?id=71']]) {
        await page.setViewportSize({
          width,
          height: 1000
        });
        await page.goto(base + url, {
          waitUntil: 'domcontentloaded',
          timeout: 120000
        });
        await page.locator('.local-course-banner-builder-native-shell--mounted').waitFor({
          timeout: 60000
        });
        await page.waitForTimeout(2200);
        const endTour = page.locator('[data-role="end"]:visible').first();

        if (await endTour.count()) {
          await endTour.click();
          await page.waitForTimeout(200);
        }

        const row = {
          format,
          width,
          context,
          states: []
        };
        result.rows.push(row);
        row.states.push({
          state: 'initial',
          ...(await sample())
        });
        save();
        const toggles = page.locator('[data-toggler="drawers"]:visible');
        let toggle = null;

        for (let i = 0; i < (await toggles.count()); i++) {
          const e = toggles.nth(i);
          const t = await e.getAttribute('data-target');

          if (t && t.includes('courseindex')) {
            toggle = e;
            break;
          }
        }

        if (!toggle) {
          for (let i = 0; i < (await toggles.count()); i++) {
            const e = toggles.nth(i);

            if (await e.isEnabled()) {
              toggle = e;
              break;
            }
          }
        }

        if (toggle) {
          for (const state of ['toggle-1', 'toggle-2']) {
            const endTour = page.locator('[data-role="end"]:visible').first();

            if (await endTour.count()) {
              await endTour.click();
              await page.waitForTimeout(200);
            }

            const inside = page.locator('.drawer.show [data-toggler="drawers"]:visible').first();

            if (await inside.count()) {
              await inside.click();
            } else {
              await toggle.click();
            }

            for (let i = 0; i < 12; i++) {
              await page.waitForTimeout(40);
              row.states.push({
                state,
                frame: i,
                ...(await sample())
              });
            }

            await page.waitForTimeout(500);
            row.states.push({
              state: state + '-settled',
              ...(await sample())
            });
            save();
          }
        } else {
          row.drawerUnavailable = true;
          save();
        }

        for (const sample of row.states) {
          expect(sample.oracle, 'Independent page content landmark').toBeTruthy();
          expect(Math.abs(sample.shell.left - sample.oracle.left), context + ' ' + sample.state + ' left').toBeLessThanOrEqual(0.5);
          expect(Math.abs(sample.shell.right - sample.oracle.right), context + ' ' + sample.state + ' right').toBeLessThanOrEqual(0.5);
        }

        if (format === 'standard') await page.screenshot({
          path: out + '/' + phase + '-' + context + '-' + width + '.png'
        });
      }
    }

    save();
  } catch (e) {
    result.failure = e.message;
    save();
    throw e;
  } finally {
    await browser.close();
    save();
  }
});
