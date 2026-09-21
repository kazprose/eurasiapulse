// Quick viewport screenshot helper: node dev/shot.mjs <path> <width> <height> <out.png> [scrollY]
import { chromium } from 'playwright';
const [ path, width, height, out, scrollY = '0' ] = process.argv.slice( 2 );
const browser = await chromium.launch();
const page = await browser.newPage( { viewport: { width: Number( width ), height: Number( height ) } } );
await page.goto( 'http://127.0.0.1:8080' + path, { waitUntil: 'networkidle' } );
await page.evaluate( () => document.fonts.ready );
await page.evaluate( ( y ) => window.scrollTo( 0, y ), Number( scrollY ) );
await page.waitForTimeout( 200 );
await page.screenshot( { path: out } );
await browser.close();
