/**
 * Lighthouse (mobile) for the homepage, an article and a section archive.
 * Uses Playwright's Chromium. Reports go to screenshots/lighthouse-*.html/json.
 *   node dev/lighthouse.mjs
 */
import lighthouse from 'lighthouse';
import { launch } from 'chrome-launcher';
import { chromium } from 'playwright';
import { writeFileSync, mkdirSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const base = 'http://127.0.0.1:8080';
const outDir = resolve( root, 'screenshots' );
mkdirSync( outDir, { recursive: true } );

const posts = await ( await fetch( `${ base }/wp-json/wp/v2/posts?per_page=50&_fields=id,featured_media,link` ) ).json();
const article = posts.find( ( p ) => p.featured_media );
const targets = {
	home: '/',
	article: article ? new URL( article.link ).pathname : '/',
	category: '/category/politics/',
};

const chrome = await launch( {
	chromePath: chromium.executablePath(),
	chromeFlags: [ '--headless=new', '--no-sandbox', '--disable-gpu' ],
} );

const summary = [];
try {
	for ( const [ name, path ] of Object.entries( targets ) ) {
		const result = await lighthouse( base + path, {
			port: chrome.port,
			output: [ 'html', 'json' ],
			logLevel: 'error',
			onlyCategories: [ 'performance', 'accessibility', 'best-practices', 'seo' ],
		} );
		const { lhr } = result;
		writeFileSync( resolve( outDir, `lighthouse-${ name }.html` ), result.report[ 0 ] );
		writeFileSync( resolve( outDir, `lighthouse-${ name }.json` ), result.report[ 1 ] );
		const scores = Object.fromEntries( Object.entries( lhr.categories ).map( ( [ k, v ] ) => [ k, Math.round( v.score * 100 ) ] ) );
		const failing = Object.values( lhr.audits )
			.filter( ( a ) => a.score !== null && a.score < 1 && a.scoreDisplayMode !== 'informative' )
			.map( ( a ) => `${ a.id } (${ a.score })` );
		summary.push( { name, url: base + path, scores, failing, lcp: lhr.audits[ 'largest-contentful-paint' ]?.displayValue, cls: lhr.audits[ 'cumulative-layout-shift' ]?.displayValue, tbt: lhr.audits[ 'total-blocking-time' ]?.displayValue } );
		console.log( name, JSON.stringify( scores ), 'LCP', lhr.audits[ 'largest-contentful-paint' ]?.displayValue, 'CLS', lhr.audits[ 'cumulative-layout-shift' ]?.displayValue );
		if ( failing.length ) {
			console.log( '  failing/partial audits:', failing.join( ', ' ) );
		}
	}
} finally {
	await chrome.kill();
}
writeFileSync( resolve( outDir, 'lighthouse-summary.json' ), JSON.stringify( summary, null, 2 ) );
