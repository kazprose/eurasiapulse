/**
 * Generate languages/eurasiapulse.pot from the theme's PHP sources
 * (no WP-CLI / gettext needed). Handles __, _e, esc_html__, esc_html_e,
 * esc_attr__, esc_attr_e, _x, _n, _nx, esc_html_x, esc_attr_x with translator comments.
 *   node dev/make-pot.mjs
 */
import { readFileSync, writeFileSync, readdirSync, statSync } from 'node:fs';
import { resolve, dirname, relative, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..', 'eurasiapulse' );
const files = [];
( function walk( dir ) {
	for ( const entry of readdirSync( dir ) ) {
		const full = join( dir, entry );
		if ( statSync( full ).isDirectory() ) {
			walk( full );
		} else if ( entry.endsWith( '.php' ) ) {
			files.push( full );
		}
	}
} )( root );

const str = `(?:'((?:[^'\\\\]|\\\\.)*)'|"((?:[^"\\\\]|\\\\.)*)")`;
const ws = `\\s*`;
const patterns = [
	{ re: new RegExp( `\\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\\(${ ws }${ str }${ ws },${ ws }'eurasiapulse'`, 'g' ), map: ( m ) => ( { msgid: m[ 1 ] ?? m[ 2 ] } ) },
	{ re: new RegExp( `\\b(?:_x|esc_html_x|esc_attr_x)\\(${ ws }${ str }${ ws },${ ws }${ str }${ ws },${ ws }'eurasiapulse'`, 'g' ), map: ( m ) => ( { msgid: m[ 1 ] ?? m[ 2 ], msgctxt: m[ 3 ] ?? m[ 4 ] } ) },
	{ re: new RegExp( `\\b_n\\(${ ws }${ str }${ ws },${ ws }${ str }${ ws },[^,]+,${ ws }'eurasiapulse'`, 'g' ), map: ( m ) => ( { msgid: m[ 1 ] ?? m[ 2 ], plural: m[ 3 ] ?? m[ 4 ] } ) },
];

const entries = new Map();
for ( const file of files ) {
	const src = readFileSync( file, 'utf8' );
	const rel = relative( resolve( root, '..' ), file ).replace( /\\/g, '/' );
	for ( const { re, map } of patterns ) {
		let m;
		while ( ( m = re.exec( src ) ) ) {
			const e = map( m );
			const line = src.slice( 0, m.index ).split( '\n' ).length;
			const before = src.slice( Math.max( 0, m.index - 300 ), m.index );
			const comment = before.match( /\/\*\s*translators:\s*([^*]+?)\s*\*\/\s*$/ );
			const key = `${ e.msgctxt ?? '' }${ e.msgid }`;
			if ( ! entries.has( key ) ) {
				entries.set( key, { ...e, refs: [], comment: comment ? comment[ 1 ].replace( /\s+/g, ' ' ) : '' } );
			}
			entries.get( key ).refs.push( `${ rel }:${ line }` );
		}
	}
}

const esc = ( s ) => s.replace( /\\'/g, "'" ).replace( /\\/g, '\\\\' ).replace( /"/g, '\\"' ).replace( /\n/g, '\\n' );
let out = `# EurasiaPulse WordPress theme.
# This file is distributed under the GNU General Public License v2 or later.
msgid ""
msgstr ""
"Project-Id-Version: EurasiaPulse 1.0.0\\n"
"Report-Msgid-Bugs-To: https://eurasiapulse.com/\\n"
"POT-Creation-Date: ${ new Date().toISOString().replace( 'T', ' ' ).slice( 0, 16 ) }+00:00\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\\n"
"X-Domain: eurasiapulse\\n"
"Language: \\n"

`;
for ( const e of entries.values() ) {
	if ( e.comment ) {
		out += `#. translators: ${ e.comment }\n`;
	}
	out += `#: ${ e.refs.join( ' ' ) }\n`;
	if ( e.msgctxt ) {
		out += `msgctxt "${ esc( e.msgctxt ) }"\n`;
	}
	out += `msgid "${ esc( e.msgid ) }"\n`;
	if ( e.plural ) {
		out += `msgid_plural "${ esc( e.plural ) }"\nmsgstr[0] ""\nmsgstr[1] ""\n\n`;
	} else {
		out += `msgstr ""\n\n`;
	}
}
writeFileSync( resolve( root, 'languages', 'eurasiapulse.pot' ), out );
console.log( `Wrote ${ entries.size } strings from ${ files.length } files to languages/eurasiapulse.pot` );
