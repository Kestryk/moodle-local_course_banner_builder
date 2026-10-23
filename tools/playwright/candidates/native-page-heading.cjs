const fs=require('node:fs'),path=require('node:path');
const [modulePath,baseURL,courseId,output]=process.argv.slice(2);
if(!modulePath||!baseURL||!courseId||!output)throw Error('Pass absolute Playwright module, base URL, course id and artifact directory');
if(!path.isAbsolute(modulePath)||!path.isAbsolute(output))throw Error('Absolute paths required');
if(!/^\d+$/.test(courseId))throw Error('Invalid course id');
if(!process.env.EASYEDU_TEST_USER||!process.env.EASYEDU_TEST_PASSWORD)throw Error('Supply test credentials in environment');
fs.mkdirSync(output,{recursive:true});
const {chromium}=require(modulePath);
const site=new URL(baseURL).hostname.replace(/[^a-zA-Z0-9.-]/g,'-');
(async()=>{const browser=await chromium.launch({headless:true});const out={site,checks:[],errors:[],console:[],failedResponses:[]};
try{const p=await browser.newPage({viewport:{width:1600,height:1000}}),base=baseURL.replace(/\/$/,'');
p.on('pageerror',e=>out.errors.push(e.message));p.on('console',m=>{if(m.type()==='error')out.console.push({text:m.text(),location:m.location()});});
p.on('response',r=>{if(r.status()>=400)out.failedResponses.push({url:r.url(),status:r.status()});});
await p.goto(base+'/login/index.php',{waitUntil:'domcontentloaded',timeout:90000});
await p.fill('#username',process.env.EASYEDU_TEST_USER);await p.fill('#password',process.env.EASYEDU_TEST_PASSWORD);
await Promise.all([p.waitForURL(u=>!u.pathname.includes('/login/'),{timeout:90000}),p.locator('#loginbtn').click()]);
const set=async(scope,on)=>{await p.goto(base+'/local/course_banner_builder/'+(scope==='site'?'admin_site.php':'admin_manage.php'),{waitUntil:'domcontentloaded',timeout:90000});
 await p.waitForTimeout(1200);
 const tourEnd=p.locator('[data-role="flexitour-step"] [data-role="end"]:visible');
 if(await tourEnd.count()){await tourEnd.first().click();await p.waitForTimeout(500);}
 const input=p.locator(`input[type=checkbox][name="s_local_course_banner_builder_hidepagetitle_${scope}"]`);await input.waitFor({state:'attached',timeout:30000});
 await input.setChecked(on);const form=input.locator('xpath=ancestor::form');
 await Promise.all([p.waitForNavigation({waitUntil:'domcontentloaded',timeout:90000}),form.locator('button[type=submit]').click()]);};
const check=async(view,expected)=>{await p.goto(base+(view==='dashboard'?'/my/':'/course/view.php?id='+courseId),{waitUntil:'networkidle',timeout:90000});await p.waitForTimeout(1500);
 for(let n=0;n<3;n++){const end=p.locator('[data-role="flexitour-step"] [data-role="end"]:visible');if(!await end.count())break;await end.first().click();await p.waitForTimeout(500);}
 const r=await p.evaluate(()=>{const shell=document.querySelector('.local-course-banner-builder-native-shell'),header=document.querySelector('#page-header'),h=header?.querySelector('h1');
 const visible=e=>!!e&&e.getClientRects().length>0&&getComputedStyle(e).display!=='none'&&getComputedStyle(e).visibility!=='hidden';
 return {headingVisible:visible(h),bannerVisible:visible(shell),beforeHeader:!!shell&&!!header&&!!(shell.compareDocumentPosition(header)&Node.DOCUMENT_POSITION_FOLLOWING),shellY:shell?.getBoundingClientRect().y,headerY:header?.getBoundingClientRect().y,headingText:h?.textContent};});
 out.checks.push({view,expectedHidden:expected,...r});if(r.headingVisible===expected||!r.bannerVisible||expected&&!r.beforeHeader)throw Error('Policy mismatch '+JSON.stringify(r));
 await p.screenshot({path:path.join(output,`${site}-toggle-${out.checks.length}-${view}.png`)});};
await check('dashboard',true);await check('course',true);
await set('site',false);await check('dashboard',false);await check('course',true);
await set('site',true);await set('course',false);await check('dashboard',true);await check('course',false);
await set('course',true);await check('course',true);
if(out.errors.length||out.console.length||out.failedResponses.length)throw Error('Page/console/HTTP errors recorded; inspect the report');
out.passed=true;
}finally{await browser.close();fs.writeFileSync(path.join(output,`${site}-toggle-policy.json`),JSON.stringify(out,null,2));}
console.log(JSON.stringify(out));})().catch(e=>{console.error(e.message);process.exitCode=1;});
