const assert = require('node:assert/strict');

const fs = require('node:fs');

const vm = require('node:vm');

const {
  execFileSync
} = require('node:child_process');

const path = require('node:path');

const root = path.resolve(__dirname, '..'); // Reflection executes the real PHP producers without bootstrapping a served Moodle.

const php = process.env.CCB_TEST_PHP;

if (!php) {
  throw new Error('Set CCB_TEST_PHP to a PHP CLI executable');
}

const code = "define('MOODLE_INTERNAL',true);require $argv[1].'/classes/hook_callbacks.php';$r=new ReflectionClass('local_course_banner_builder\\hook_callbacks');$a=[];foreach(['get_native_course_banner_mount_script','get_site_banner_mount_script'] as $m){$f=$r->getMethod($m);$f->setAccessible(true);$a[$m]=$f->invoke(null);}echo json_encode($a);";
const scripts = JSON.parse(execFileSync(php, ['-r', code, root], {
  encoding: 'utf8'
}).replace(/^\uFEFF/, ''));

for (const script of Object.values(scripts)) {
  new vm.Script(script);
  assert(script.includes('new window.ResizeObserver'));
  assert(script.includes('observeContentAlignment(shell)'));
}

assert.equal(Object.values(scripts).length, 2);
console.log('PASS both PHP-generated mount scripts parse and share observed content alignment');
