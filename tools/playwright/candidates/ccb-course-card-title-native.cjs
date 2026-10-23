const fs=require('fs');
const {chromium}=require(process.env.EASYEDU_PLAYWRIGHT_MODULE || 'playwright');
const path=require('node:path');
const dir=process.env.EASYEDU_TEST_OUTPUT, fixtureFile=process.env.EASYEDU_TEST_FIXTURE;
const base=process.env.EASYEDU_TEST_BASE_URL;
if(!dir||!fixtureFile||!base||!path.isAbsolute(dir)||!path.isAbsolute(fixtureFile))throw Error('Set owned base URL and absolute fixture/output paths');
fs.mkdirSync(dir,{recursive:true});
const fixture=JSON.parse(fs.readFileSync(fixtureFile,'utf8').replace(/^\uFEFF/,''));
(async()=>{const browser=await chromium.launch({headless:true,executablePath:process.env.EASYEDU_CHROME_PATH}),result={rows:[],pageerrors:[],console:[],requests:[]};
try{const context=await browser.newContext({viewport:{width:1600,height:1000}}),page=await context.newPage();page.setDefaultTimeout(45000);page.setDefaultNavigationTimeout(120000);
page.on('pageerror',e=>result.pageerrors.push({message:e.message,stack:e.stack}));page.on('console',m=>{if(m.type()==='error')result.console.push(m.text())});page.on('response',r=>{if(/course_banner_builder|overviewfiles/.test(r.url()))result.requests.push({url:r.url(),status:r.status()})});
console.log('Native: authenticate');const login=await context.request.get(base+'/login/index.php',{timeout:60000});const token=(await login.text()).match(/name="logintoken" value="([^"]+)"/)?.[1];if(!token)throw Error('Missing login token');await context.request.post(base+'/login/index.php',{timeout:60000,maxRedirects:0,form:{username:fixture.username,password:fixture.password,logintoken:token}});
await page.goto(base+'/my/?lang=en',{waitUntil:'domcontentloaded'});console.log('Native: awaiting real cards, not skeletons');await page.waitForSelector('[data-course-id="'+fixture.courses.short+'"]',{timeout:120000});
await page.waitForFunction(()=>document.querySelectorAll('.local-course-banner-builder-course-card-title').length>=2,null,{timeout:60000});
for(let i=0;i<5;i++){const next=page.getByRole('button',{name:/^(Got it|End tour|Next|Terminer|Suivant|Compris)$/i});if(!(await next.count()))break;await next.first().click();await page.waitForTimeout(500);}
await page.waitForFunction(()=>{const e=document.querySelector('.local-course-banner-builder-course-card-title__text');return e && getComputedStyle(e).webkitLineClamp==='2';},null,{timeout:60000});
for(const width of [1600,768,390]){
 await page.setViewportSize({width,height:1000});await page.waitForTimeout(1000);await page.evaluate(()=>document.fonts.ready);
 const rows=await page.evaluate(async(courses)=>{
  const rect=e=>{if(!e)return null;const r=e.getBoundingClientRect();return {x:r.x,y:r.y,w:r.width,h:r.height,right:r.right,bottom:r.bottom}};
  const result=[];
  for(const [name,id]of Object.entries(courses)){
   const card=document.querySelector('[data-course-id="'+id+'"]'),image=card?.querySelector('.card-img-top'),title=card?.querySelector('.local-course-banner-builder-course-card-title'),native=card?.querySelector('.coursename');
   const cs=image&&getComputedStyle(image),ts=title&&getComputedStyle(title),imageUrl=cs?.backgroundImage.match(/url\(["']?(.*?)["']?\)/)?.[1];
   let pixels=null;
   if(imageUrl&&!imageUrl.startsWith('data:image/svg')){
    const img=new Image();img.src=imageUrl;await img.decode();const cv=document.createElement('canvas');cv.width=32;cv.height=16;const ctx=cv.getContext('2d');ctx.drawImage(img,0,0,32,16);const a=ctx.getImageData(0,0,32,16).data;let notWhite=0;const colors=new Set();for(let i=0;i<a.length;i+=4){if(Math.min(a[i],a[i+1],a[i+2])<235)notWhite++;colors.add(a[i]+','+a[i+1]+','+a[i+2]);}pixels={naturalWidth:img.naturalWidth,naturalHeight:img.naturalHeight,nonWhiteRatio:notWhite/512,colors:colors.size};
   }
   result.push({name,id,card:rect(card),image:rect(image),imageUrl,pixels,overlay:rect(title),overlayText:title?.textContent,titleAttribute:native?.querySelector('[title]')?.getAttribute('title'),nativeText:native?.textContent.replace(/\s+/g,' ').trim(),textBox:(()=>{const e=title?.querySelector('.local-course-banner-builder-course-card-title__text');if(!e)return null;const s=getComputedStyle(e);return {box:rect(e),padding:s.padding,lineHeight:s.lineHeight,lineClamp:s.webkitLineClamp,scrollHeight:e.scrollHeight,clientHeight:e.clientHeight};})(),titleStyle:ts&&{font:ts.font,fontFamily:ts.fontFamily,fontWeight:ts.fontWeight,color:ts.color,lineHeight:ts.lineHeight,padding:ts.padding,lineClamp:ts.webkitLineClamp,overflow:ts.overflow,textOverflow:ts.textOverflow,wordBreak:ts.wordBreak,hyphens:ts.hyphens,paintedLines:(title.clientHeight-parseFloat(ts.paddingTop)-parseFloat(ts.paddingBottom))/parseFloat(ts.lineHeight),scrollHeight:title.scrollHeight,clientHeight:title.clientHeight},documentOverflow:Math.max(0,document.documentElement.scrollWidth-innerWidth)});
  }
  return result;
 },fixture.courses);
 result.rows.push({width,url:page.url(),courses:rows});fs.writeFileSync(dir+'/native.json',JSON.stringify(result,null,2));
 const grid=page.locator('[data-region="card-deck"]').first();await grid.screenshot({path:dir+'/cards-'+width+'.png'});await page.locator('.course-card[data-course-id="'+fixture.courses.long+'"]').screenshot({path:dir+'/long-'+width+'.png'});
 console.log(JSON.stringify({width,courses:rows.map(r=>({name:r.name,image:r.imageUrl?.startsWith('data:')?'native SVG':r.imageUrl,pixels:r.pixels,title:r.overlayText,textBox:r.textBox}))}));
}
await page.setViewportSize({width:1600,height:1000});await page.goto(base+'/course/view.php?id='+fixture.courses.short+'&lang=en',{waitUntil:'domcontentloaded'});await page.waitForSelector('.local-course-banner-builder-banner-title-overlay',{timeout:60000});await page.waitForTimeout(1000);
result.fullBanner=await page.locator('.local-course-banner-builder-banner-title-overlay').first().evaluate(e=>{const s=getComputedStyle(e);return {text:e.textContent.trim(),fontFamily:s.fontFamily,fontWeight:s.fontWeight,fontSize:s.fontSize,color:s.color}});await page.locator('#local-course-banner-builder-native-shell').screenshot({path:dir+'/full-banner.png'});
}catch(e){result.failure={message:e.message,stack:e.stack};process.exitCode=1;}finally{fs.writeFileSync(dir+'/native.json',JSON.stringify(result,null,2));await browser.close();console.log(JSON.stringify({cases:result.rows.length,errors:result.pageerrors,console:result.console,failure:result.failure}));}})();
