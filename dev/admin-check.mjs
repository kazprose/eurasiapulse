/**
 * wp-admin smoke test: log in, open the block editor (meta box), the classic
 * taxonomy screens and the Customizer, capture screenshots and console errors.
 * WP_DEBUG notices land in .local/debug.log — inspect it after this run.
 *   node dev/admin-check.mjs
 */
import { chromium } from 'playwright';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const base = 'http://127.0.0.1:8080';
const out = ( name ) => resolve( root, 'screenshots', `admin-${ name }.png` );

const browser = await chromium.launch();
const page = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
const errors = [];
page.on( 'pageerror', ( err ) => errors.push( String( err ) ) );
page.on( 'console', ( msg ) => { if ( msg.type() === 'error' && ! /favicon|404/.test( msg.text() ) ) errors.push( msg.text() ); } );

await page.goto( `${ base }/wp-login.php`, { waitUntil: 'networkidle' } );
await page.fill( '#user_login', 'admin' );
await page.fill( '#user_pass', 'admin-local-password' );
await Promise.all( [ page.waitForNavigation( { waitUntil: 'networkidle' } ), page.click( '#wp-submit' ) ] );
console.log( 'logged in:', page.url() );

// Block editor with the Article details meta box.
const posts = await ( await fetch( `${ base }/wp-json/wp/v2/posts?per_page=1&_fields=id` ) ).json();
await page.goto( `${ base }/wp-admin/post.php?post=${ posts[ 0 ].id }&action=edit`, { waitUntil: 'networkidle' } );
await page.waitForSelector( '.editor-post-title__input, .edit-post-visual-editor', { timeout: 60000 } );
try { await page.click( 'button[aria-label="Close"]', { timeout: 3000 } ); } catch ( e ) { /* no welcome guide */ }
await page.waitForTimeout( 1500 );
const metaBox = await page.locator( '#eurasiapulse-article' ).count();
console.log( 'meta box present in block editor:', metaBox > 0 );
await page.locator( '#eurasiapulse-article' ).scrollIntoViewIfNeeded().catch( () => {} );
await page.screenshot( { path: out( 'block-editor' ), fullPage: false } );

// Taxonomy screens.
for ( const tax of [ 'region', 'format' ] ) {
	await page.goto( `${ base }/wp-admin/edit-tags.php?taxonomy=${ tax }`, { waitUntil: 'networkidle' } );
	console.log( `${ tax } screen title:`, await page.title() );
}
await page.screenshot( { path: out( 'formats' ), fullPage: false } );

// Customizer with the EurasiaPulse panel open.
await page.goto( `${ base }/wp-admin/customize.php`, { waitUntil: 'networkidle' } );
await page.waitForSelector( '#accordion-panel-eurasiapulse', { timeout: 60000 } );
await page.click( '#accordion-panel-eurasiapulse h3' );
await page.waitForTimeout( 800 );
await page.click( '#accordion-section-eurasiapulse_home h3' );
await page.waitForTimeout( 1500 );
await page.screenshot( { path: out( 'customizer' ), fullPage: false } );
console.log( 'customizer controls in Homepage section:', await page.locator( '#sub-accordion-section-eurasiapulse_home li.customize-control' ).count() );

await browser.close();
console.log( errors.length ? `JS/console errors: ${ JSON.stringify( errors.slice( 0, 10 ), null, 1 ) }` : 'No JS/console errors in admin screens.' );
