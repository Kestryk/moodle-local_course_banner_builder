const assert = require('node:assert/strict'),
      fs = require('node:fs'),
      path = require('node:path'),
      {
  execFileSync
} = require('node:child_process');

const {
  chromium
} = require(process.env.CCB_TEST_PLAYWRIGHT);

const root = path.resolve(__dirname, '../..');
const source = "define('MOODLE_INTERNAL',true);require $argv[1].'/classes/hook_callbacks.php';$r=new ReflectionClass('local_course_banner_builder\\hook_callbacks');$a=[];foreach(['get_native_course_banner_mount_script','get_site_banner_mount_script','get_course_banner_runtime_css'] as $m){$f=$r->getMethod($m);$f->setAccessible(true);$a[$m]=$f->invoke(null);}echo json_encode($a);";
const scripts = JSON.parse(execFileSync(process.env.CCB_TEST_PHP, ['-r', source, root], {
  encoding: 'utf8'
}));
const runtimeCss = scripts.get_course_banner_runtime_css;
delete scripts.get_course_banner_runtime_css;
(async () => {
  const browser = await chromium.launch({
    headless: true,
    executablePath: process.env.CCB_TEST_CHROME
  });
  const rows = [],
        errors = [];

  try {
    const page = await browser.newPage();
    page.on('pageerror', e => errors.push(e.message));

    for (const [name, script] of Object.entries(scripts)) for (const format of ['standard', 'contentwide', 'fullwidthtop', 'fullwidthtopcompact', 'fullwidthtopinset']) for (const width of [1600, 768]) {
      await page.goto('about:blank');
      await page.setViewportSize({
        width,
        height: 1000
      });
      const id = name.includes('native_course') ? 'local-course-banner-builder-native-shell' : 'local-course-banner-builder-site-shell';
      await page.setContent(`<style>${runtimeCss}</style><style>body{margin:0}#page{padding:0 40px}#page.open{padding-left:280px}#page-header{height:40px}.main-inner{max-width:1320px;margin:auto}#region-main{padding:0}#wrapper{padding:0 15px}.frame{height:200px;border:1px solid #aaa;background:white}#${id}{height:100px;width:100%;box-sizing:border-box}</style><nav class="navbar fixed-top"></nav><div id="page"><div class="main-inner"><header id="page-header"></header><div id="${id}" class="local-course-banner-builder-native-shell local-course-banner-builder-native-shell--format-${format}" data-banner-format="${format}"></div><div id="region-main"><div id="wrapper"><section class="frame"></section></div></div></div></div>`);
      assert.equal(await page.locator('#' + id).evaluate(e => getComputedStyle(e).visibility), 'hidden');
      await page.addScriptTag({
        content: script
      });
      const first = await page.evaluate(id => new Promise(resolve => requestAnimationFrame(() => {
        const shell = document.getElementById(id),
              target = document.querySelector('.frame');
        const a = shell.getBoundingClientRect(),
              b = target.getBoundingClientRect();
        resolve({
          left: a.left - b.left,
          right: a.right - b.right,
          visible: getComputedStyle(shell).visibility
        });
      })), id);
      rows.push({
        name,
        format,
        width,
        state: 'first-paint',
        ...first
      });
      assert.equal(first.visible, 'visible');
      assert(Math.abs(first.left) <= 0.1 && Math.abs(first.right) <= 0.1, JSON.stringify(rows.at(-1)));

      for (const state of ['closed', 'open', 'closed-again', 'replace-content']) {
        await page.evaluate(state => {
          document.querySelector('#page').classList.toggle('open', state === 'open');

          if (state === 'replace-content') {
            document.querySelector('#wrapper').innerHTML = '<div style="padding:0 12px"><section class="frame"></section></div>';
          }
        }, state);
        await page.waitForTimeout(100);
        const d = await page.evaluate(id => {
          const a = document.getElementById(id).getBoundingClientRect(),
                b = document.querySelector('.frame').getBoundingClientRect();
          return {
            left: a.left - b.left,
            right: a.right - b.right
          };
        }, id);
        rows.push({
          name,
          format,
          width,
          state,
          ...d
        });
        assert(Math.abs(d.left) <= 0.1 && Math.abs(d.right) <= 0.1, JSON.stringify(rows.at(-1)));
      }
    }

    assert.deepEqual(errors, []);
    fs.writeFileSync(process.env.CCB_TEST_RESULTS, JSON.stringify({
      rows,
      errors
    }, null, 2));
    console.log('PASS ' + rows.length + ' content/frame/drawer/replacement cases');
  } finally {
    await browser.close();
  }
})().catch(e => {
  console.error(e);
  process.exitCode = 1;
});
