/**
 * Full-page screenshots of every template at 1440 / 768 / 375 px, plus the
 * theme screenshot.png (1200x900). Requires the local server on :8080.
 *   node dev/screenshots.mjs
 */
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const base = 'http://127.0.0.1:8080';
const outDir = resolve( root, 'screenshots' );
mkdirSync( outDir, { recursive: true } );

const posts = await ( await fetch( `${ base }/wp-json/wp/v2/posts?per_page=50&_fields=id,slug,title,featured_media,link` ) ).json();
const withImage = posts.find( ( p ) => p.featured_media );
const noImage = posts.find( ( p ) => ! p.featured_media );
const longTitle = posts.find( ( p ) => p.title.rendered.length > 100 );
const cyrillic = posts.find( ( p ) => /[Ѐ-ӿ]/.test( p.title.rendered ) );

const pages = {
	home: '/',
	'single': withImage ? new URL( withImage.link ).pathname : '/',
	'single-no-image': noImage ? new URL( noImage.link ).pathname : '/',
	'single-long-title': longTitle ? new URL( longTitle.link ).pathname : '/',
	'single-kazakh': cyrillic ? new URL( cyrillic.link ).pathname : '/',
	category: '/category/politics/',
	region: '/region/kazakhstan/',
	format: '/format/analysis/',
	author: '/author/sample-author-one/',
	search: '/?s=sample',
	page: '/about/',
	'404': '/no-such-page/',
};
const widths = [ 1440, 768, 375 ];

const browser = await chromium.launch();
const results = [];
for ( const width of widths ) {
	const context = await browser.newContext( { viewport: { width, height: 900 }, deviceScaleFactor: 1 } );
	const page = await context.newPage();
	const errors = [];
	page.on( 'console', ( msg ) => { if ( msg.type() === 'error' ) errors.push( msg.text() ); } );
	page.on( 'pageerror', ( err ) => errors.push( String( err ) ) );
	for ( const [ name, path ] of Object.entries( pages ) ) {
		await page.goto( base + path, { waitUntil: 'networkidle' } );
		await page.evaluate( () => document.fonts.ready );
		const file = resolve( outDir, `${ name }-${ width }.png` );
		await page.screenshot( { path: file, fullPage: true } );
		const overflow = await page.evaluate( () => document.documentElement.scrollWidth > document.documentElement.clientWidth );
		results.push( { name, width, overflow } );
		if ( name === 'home' && width === 375 ) {
			await page.click( '[data-nav-toggle]' );
			await page.screenshot( { path: resolve( outDir, `home-${ width }-menu-open.png` ), fullPage: false } );
			await page.keyboard.press( 'Escape' );
			await page.click( '[data-search-toggle]' );
			await page.screenshot( { path: resolve( outDir, `home-${ width }-search-open.png` ), fullPage: false } );
		}
	}
	if ( errors.length ) {
		console.log( `Console errors at ${ width }px:`, errors );
	}
	await context.close();
}

// Theme thumbnail: 1200x900 viewport of the homepage.
const shot = await browser.newContext( { viewport: { width: 1200, height: 900 }, deviceScaleFactor: 1 } );
const shotPage = await shot.newPage();
await shotPage.goto( base + '/', { waitUntil: 'networkidle' } );
await shotPage.evaluate( () => document.fonts.ready );
await shotPage.screenshot( { path: resolve( root, 'eurasiapulse', 'screenshot.png' ), fullPage: false } );
await shot.close();
await browser.close();

const overflowing = results.filter( ( r ) => r.overflow );
writeFileSync( resolve( outDir, 'index.txt' ), results.map( ( r ) => `${ r.name }-${ r.width }.png${ r.overflow ? '  (HORIZONTAL OVERFLOW!)' : '' }` ).join( '\n' ) + '\n' );
console.log( `Saved ${ results.length } screenshots to ${ outDir }` );
console.log( overflowing.length ? `Horizontal overflow on: ${ overflowing.map( ( r ) => `${ r.name }@${ r.width }` ).join( ', ' ) }` : 'No horizontal overflow at any width.' );
