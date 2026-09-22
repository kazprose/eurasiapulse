/**
 * EurasiaPulse front-end behaviour (vanilla JS, no dependencies).
 * - Mobile navigation toggle (full-screen panel)
 * - Header search toggle
 * - "Copy link" share button
 */
( function () {
	'use strict';

	var nav = document.getElementById( 'site-nav' );
	var navToggle = document.querySelector( '[data-nav-toggle]' );
	var menu = document.getElementById( 'primary-menu' );
	var searchToggle = document.querySelector( '[data-search-toggle]' );
	var searchPanel = document.getElementById( 'header-search' );
	var desktop = window.matchMedia( '(min-width: 900px)' );

	function setMenu( open ) {
		if ( ! nav || ! navToggle ) {
			return;
		}
		nav.classList.toggle( 'nav--open', open );
		navToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		document.body.classList.toggle( 'menu-open', open );
		if ( open ) {
			window.scrollTo( 0, nav.offsetTop );
		}
	}

	function setSearch( open ) {
		if ( ! searchPanel || ! searchToggle ) {
			return;
		}
		searchPanel.hidden = ! open;
		searchToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		if ( open ) {
			var input = searchPanel.querySelector( 'input[type="search"]' );
			if ( input ) {
				input.focus();
			}
		}
	}

	if ( navToggle && menu ) {
		navToggle.addEventListener( 'click', function () {
			setMenu( navToggle.getAttribute( 'aria-expanded' ) !== 'true' );
		} );
		if ( desktop.addEventListener ) {
			desktop.addEventListener( 'change', function ( event ) {
				if ( event.matches ) {
					setMenu( false );
				}
			} );
		}
	}

	if ( searchToggle && searchPanel ) {
		searchToggle.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			setSearch( searchPanel.hidden );
		} );
	}

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key !== 'Escape' ) {
			return;
		}
		if ( searchPanel && ! searchPanel.hidden ) {
			setSearch( false );
			searchToggle.focus();
		}
		if ( nav && nav.classList.contains( 'nav--open' ) ) {
			setMenu( false );
			navToggle.focus();
		}
	} );

	// Light / dark mode switch. The stored choice is applied before paint by an
	// inline script in the head; here we only toggle and persist it.
	var themeToggle = document.querySelector( '[data-theme-toggle]' );
	if ( themeToggle ) {
		var root = document.documentElement;
		var prefersDark = window.matchMedia( '(prefers-color-scheme: dark)' );
		var currentTheme = function () {
			var explicit = root.getAttribute( 'data-theme' );
			if ( explicit ) {
				return explicit;
			}
			return ( root.hasAttribute( 'data-theme-auto' ) && prefersDark.matches ) ? 'dark' : 'light';
		};
		var renderToggle = function () {
			var dark = currentTheme() === 'dark';
			themeToggle.setAttribute( 'aria-pressed', dark ? 'true' : 'false' );
			themeToggle.setAttribute( 'aria-label', themeToggle.getAttribute( dark ? 'data-label-light' : 'data-label-dark' ) );
		};
		themeToggle.addEventListener( 'click', function () {
			var next = currentTheme() === 'dark' ? 'light' : 'dark';
			root.setAttribute( 'data-theme', next );
			try {
				localStorage.setItem( 'eurasiapulse-theme', next );
			} catch ( e ) {
				// Storage unavailable: the choice lasts for this page only.
			}
			renderToggle();
		} );
		if ( prefersDark.addEventListener ) {
			prefersDark.addEventListener( 'change', renderToggle );
		}
		renderToggle();
	}

	var copyButton = document.querySelector( '[data-copy-link]' );
	if ( copyButton && navigator.clipboard && window.isSecureContext ) {
		copyButton.hidden = false;
		copyButton.addEventListener( 'click', function () {
			var status = document.getElementById( 'share-status' );
			navigator.clipboard.writeText( copyButton.getAttribute( 'data-copy-link' ) ).then( function () {
				if ( status ) {
					status.textContent = copyButton.getAttribute( 'data-copied-label' ) || 'Link copied';
					window.setTimeout( function () {
						status.textContent = '';
					}, 2500 );
				}
			} );
		} );
	}
} )();
