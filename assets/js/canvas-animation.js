/**
 * Canvas Particle Network Animation
 *
 * Full-screen animated particle network with connection lines, mouse
 * interaction, and periodic pulse waves. Renders on an HTML5 canvas
 * element with id "rf-particle-canvas".
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */
(function () {
	'use strict';

	/* ---------------------------------------------------------------
	 * Constants
	 * ------------------------------------------------------------- */
	var CONNECTION_DISTANCE  = 150;
	var MOUSE_REPEL_DISTANCE = 200;
	var MOUSE_REPEL_FORCE    = 0.8;
	var DAMPING              = 0.99;
	var MIN_SPEED            = 0.2;
	var PULSE_INTERVAL       = 5000;   // ms between new pulses
	var PULSE_DURATION       = 2000;   // ms for a pulse to expand
	var PULSE_MAX_RADIUS     = 300;
	var MAX_CONCURRENT_PULSE = 2;
	var MOBILE_BREAKPOINT    = 768;

	/* Colours */
	var PARTICLE_COLOUR      = 'rgba(0, 255, 136, 0.3)';
	var LINE_COLOUR_R        = 0;
	var LINE_COLOUR_G        = 212;
	var LINE_COLOUR_B        = 255;
	var PULSE_COLOUR_R       = 0;
	var PULSE_COLOUR_G       = 255;
	var PULSE_COLOUR_B       = 136;

	/* ---------------------------------------------------------------
	 * State
	 * ------------------------------------------------------------- */
	var canvas, ctx;
	var particles = [];
	var pulses    = [];
	var mouseX    = -9999;
	var mouseY    = -9999;
	var mouseActive = false;
	var dpr, width, height;
	var lastPulseTime   = 0;
	var animationId     = null;
	var reducedMotion   = false;
	var resizeTimer     = null;

	/* ---------------------------------------------------------------
	 * Config (from wp_localize_script)
	 * ------------------------------------------------------------- */
	function getBaseParticleCount() {
		if ( typeof window.rfSecureLogin !== 'undefined' && window.rfSecureLogin.particleCount ) {
			var count = parseInt( window.rfSecureLogin.particleCount, 10 );
			return isNaN( count ) || count < 1 ? 80 : count;
		}
		return 80;
	}

	/* ---------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------- */

	/**
	 * Return the target particle count based on current viewport width.
	 *
	 * @return {number} Particle count.
	 */
	function getParticleCount() {
		var base = getBaseParticleCount();
		return width < MOBILE_BREAKPOINT ? Math.round( base / 2 ) : base;
	}

	/**
	 * Return a random number between min and max (inclusive).
	 *
	 * @param {number} min Lower bound.
	 * @param {number} max Upper bound.
	 * @return {number} Random value.
	 */
	function rand( min, max ) {
		return Math.random() * ( max - min ) + min;
	}

	/* ---------------------------------------------------------------
	 * Canvas Setup
	 * ------------------------------------------------------------- */

	/**
	 * Set canvas dimensions accounting for devicePixelRatio.
	 */
	function resize() {
		dpr    = window.devicePixelRatio || 1;
		width  = window.innerWidth;
		height = window.innerHeight;

		canvas.width  = width * dpr;
		canvas.height = height * dpr;
		canvas.style.width  = width + 'px';
		canvas.style.height = height + 'px';

		ctx.setTransform( dpr, 0, 0, dpr, 0, 0 );
	}

	/* ---------------------------------------------------------------
	 * Particle Creation
	 * ------------------------------------------------------------- */

	/**
	 * Create a single particle with random position, velocity, and radius.
	 *
	 * @return {Object} Particle object.
	 */
	function createParticle() {
		var angle = Math.random() * Math.PI * 2;
		var speed = rand( 0.2, 0.5 );
		return {
			x:  Math.random() * width,
			y:  Math.random() * height,
			vx: Math.cos( angle ) * speed,
			vy: Math.sin( angle ) * speed,
			r:  rand( 2, 3 )
		};
	}

	/**
	 * Fill the particles array to match the target count.
	 */
	function initParticles() {
		var count = getParticleCount();
		particles = [];
		for ( var i = 0; i < count; i++ ) {
			particles.push( createParticle() );
		}
	}

	/* ---------------------------------------------------------------
	 * Particle Update
	 * ------------------------------------------------------------- */

	/**
	 * Update particle positions, apply mouse repulsion, damping,
	 * minimum speed enforcement, and edge wrapping.
	 */
	function updateParticles() {
		var target = getParticleCount();
		var i, p, dx, dy, dist, force, speed, angle;

		/* Dynamically adjust particle count on resize */
		while ( particles.length < target ) {
			particles.push( createParticle() );
		}
		while ( particles.length > target ) {
			particles.pop();
		}

		for ( i = 0; i < particles.length; i++ ) {
			p = particles[ i ];

			/* Mouse repulsion */
			if ( mouseActive ) {
				dx   = p.x - mouseX;
				dy   = p.y - mouseY;
				dist = Math.sqrt( dx * dx + dy * dy );

				if ( dist < MOUSE_REPEL_DISTANCE && dist > 0 ) {
					force = ( MOUSE_REPEL_DISTANCE - dist ) / MOUSE_REPEL_DISTANCE * MOUSE_REPEL_FORCE;
					p.vx += ( dx / dist ) * force;
					p.vy += ( dy / dist ) * force;
				}
			}

			/* Damping */
			p.vx *= DAMPING;
			p.vy *= DAMPING;

			/* Minimum speed enforcement */
			speed = Math.sqrt( p.vx * p.vx + p.vy * p.vy );
			if ( speed < MIN_SPEED && speed > 0 ) {
				p.vx = ( p.vx / speed ) * MIN_SPEED;
				p.vy = ( p.vy / speed ) * MIN_SPEED;
			} else if ( speed === 0 ) {
				/* Particle has stopped completely — give it a random nudge */
				angle = Math.random() * Math.PI * 2;
				p.vx  = Math.cos( angle ) * MIN_SPEED;
				p.vy  = Math.sin( angle ) * MIN_SPEED;
			}

			/* Move */
			p.x += p.vx;
			p.y += p.vy;

			/* Edge wrapping */
			if ( p.x < -p.r ) {
				p.x = width + p.r;
			} else if ( p.x > width + p.r ) {
				p.x = -p.r;
			}
			if ( p.y < -p.r ) {
				p.y = height + p.r;
			} else if ( p.y > height + p.r ) {
				p.y = -p.r;
			}
		}
	}

	/* ---------------------------------------------------------------
	 * Particle Drawing
	 * ------------------------------------------------------------- */

	/**
	 * Draw all particles and connection lines between nearby particles.
	 */
	function drawParticles() {
		var i, j, p, q, dx, dy, dist, opacity;

		/* Connection lines */
		for ( i = 0; i < particles.length; i++ ) {
			p = particles[ i ];
			for ( j = i + 1; j < particles.length; j++ ) {
				q  = particles[ j ];
				dx = p.x - q.x;
				dy = p.y - q.y;

				/* Quick bounding-box rejection before sqrt */
				if ( Math.abs( dx ) > CONNECTION_DISTANCE || Math.abs( dy ) > CONNECTION_DISTANCE ) {
					continue;
				}

				dist = Math.sqrt( dx * dx + dy * dy );

				if ( dist < CONNECTION_DISTANCE ) {
					opacity = 0.15 * ( 1 - dist / CONNECTION_DISTANCE );
					ctx.beginPath();
					ctx.moveTo( p.x, p.y );
					ctx.lineTo( q.x, q.y );
					ctx.strokeStyle = 'rgba(' + LINE_COLOUR_R + ',' + LINE_COLOUR_G + ',' + LINE_COLOUR_B + ',' + opacity + ')';
					ctx.lineWidth   = 0.5;
					ctx.stroke();
				}
			}
		}

		/* Particles */
		ctx.fillStyle = PARTICLE_COLOUR;
		for ( i = 0; i < particles.length; i++ ) {
			p = particles[ i ];
			ctx.beginPath();
			ctx.arc( p.x, p.y, p.r, 0, Math.PI * 2 );
			ctx.fill();
		}
	}

	/* ---------------------------------------------------------------
	 * Pulse Waves
	 * ------------------------------------------------------------- */

	/**
	 * Create new pulse waves on interval and draw/update existing ones.
	 *
	 * @param {number} timestamp Current animation timestamp (ms).
	 */
	function updatePulses( timestamp ) {
		var i, pulse, progress, radius, opacity;

		/* Spawn new pulse if interval elapsed and under max concurrent */
		if ( timestamp - lastPulseTime > PULSE_INTERVAL && pulses.length < MAX_CONCURRENT_PULSE ) {
			pulses.push({
				x:     Math.random() * width,
				y:     Math.random() * height,
				start: timestamp
			});
			lastPulseTime = timestamp;
		}

		/* Draw and update existing pulses — iterate backwards for safe removal */
		for ( i = pulses.length - 1; i >= 0; i-- ) {
			pulse    = pulses[ i ];
			progress = ( timestamp - pulse.start ) / PULSE_DURATION;

			if ( progress >= 1 ) {
				pulses.splice( i, 1 );
				continue;
			}

			radius  = progress * PULSE_MAX_RADIUS;
			opacity = 0.2 * ( 1 - progress );

			ctx.beginPath();
			ctx.arc( pulse.x, pulse.y, radius, 0, Math.PI * 2 );
			ctx.strokeStyle = 'rgba(' + PULSE_COLOUR_R + ',' + PULSE_COLOUR_G + ',' + PULSE_COLOUR_B + ',' + opacity + ')';
			ctx.lineWidth   = 1.5;
			ctx.stroke();
		}
	}

	/* ---------------------------------------------------------------
	 * Animation Loop
	 * ------------------------------------------------------------- */

	/**
	 * Main animation frame callback.
	 *
	 * @param {number} timestamp High-resolution timestamp from rAF.
	 */
	function animate( timestamp ) {
		ctx.clearRect( 0, 0, width, height );

		updateParticles();
		drawParticles();
		updatePulses( timestamp );

		animationId = requestAnimationFrame( animate );
	}

	/* ---------------------------------------------------------------
	 * Reduced Motion — Static Draw
	 * ------------------------------------------------------------- */

	/**
	 * Draw particles once without animation, lines, or pulses.
	 * Used when `prefers-reduced-motion: reduce` is active.
	 */
	function drawStatic() {
		var i, p;

		ctx.clearRect( 0, 0, width, height );
		ctx.fillStyle = PARTICLE_COLOUR;

		for ( i = 0; i < particles.length; i++ ) {
			p = particles[ i ];
			ctx.beginPath();
			ctx.arc( p.x, p.y, p.r, 0, Math.PI * 2 );
			ctx.fill();
		}
	}

	/* ---------------------------------------------------------------
	 * Event Handlers
	 * ------------------------------------------------------------- */

	/**
	 * Track mouse position.
	 *
	 * @param {MouseEvent} e Mouse event.
	 */
	function onMouseMove( e ) {
		mouseX      = e.clientX;
		mouseY      = e.clientY;
		mouseActive = true;
	}

	/**
	 * Disable mouse tracking when cursor leaves the viewport.
	 */
	function onMouseLeave() {
		mouseActive = false;
	}

	/**
	 * Track touch position (single touch).
	 *
	 * @param {TouchEvent} e Touch event.
	 */
	function onTouchMove( e ) {
		if ( e.touches.length > 0 ) {
			mouseX      = e.touches[0].clientX;
			mouseY      = e.touches[0].clientY;
			mouseActive = true;
		}
	}

	/**
	 * Disable tracking when touch ends.
	 */
	function onTouchEnd() {
		mouseActive = false;
	}

	/**
	 * Handle viewport resize — resize canvas and adjust particles.
	 * For reduced-motion mode, also redraw the static frame.
	 */
	function onResize() {
		resize();

		if ( reducedMotion ) {
			/* Re-scatter particles for new dimensions and redraw */
			initParticles();
			drawStatic();
		}
		/* Animated mode adjusts particle count inside updateParticles() */
	}

	/**
	 * Debounced resize handler (fallback when ResizeObserver is unavailable).
	 */
	function onResizeDebounced() {
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( onResize, 150 );
	}

	/* ---------------------------------------------------------------
	 * Initialisation
	 * ------------------------------------------------------------- */

	/**
	 * Set up canvas, particles, events, and start animation.
	 */
	function init() {
		canvas = document.getElementById( 'rf-particle-canvas' );

		if ( ! canvas ) {
			return;
		}

		ctx = canvas.getContext( '2d' );

		/* Detect reduced-motion preference */
		reducedMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		/* Initial sizing */
		resize();

		/* Create particles */
		initParticles();

		if ( reducedMotion ) {
			/* Static render only — no animation loop */
			drawStatic();
		} else {
			/* Start animation */
			animationId = requestAnimationFrame( animate );

			/* Mouse / touch interaction */
			canvas.addEventListener( 'mousemove',  onMouseMove,  false );
			canvas.addEventListener( 'mouseleave', onMouseLeave, false );
			canvas.addEventListener( 'touchmove',  onTouchMove,  { passive: true } );
			canvas.addEventListener( 'touchend',   onTouchEnd,   false );
		}

		/* Resize handling */
		if ( typeof ResizeObserver !== 'undefined' ) {
			var ro = new ResizeObserver( onResize );
			ro.observe( document.body );
		} else {
			window.addEventListener( 'resize', onResizeDebounced, false );
		}

		/* Listen for changes to reduced-motion preference */
		if ( window.matchMedia ) {
			var motionQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' );
			var onMotionChange = function ( e ) {
				reducedMotion = e.matches;

				if ( reducedMotion ) {
					/* Stop animation and show static frame */
					if ( animationId ) {
						cancelAnimationFrame( animationId );
						animationId = null;
					}
					drawStatic();
				} else {
					/* Resume animation */
					if ( ! animationId ) {
						animationId = requestAnimationFrame( animate );
					}
				}
			};

			/* Support both addEventListener and deprecated addListener */
			if ( motionQuery.addEventListener ) {
				motionQuery.addEventListener( 'change', onMotionChange );
			} else if ( motionQuery.addListener ) {
				motionQuery.addListener( onMotionChange );
			}
		}
	}

	/* ---------------------------------------------------------------
	 * DOM Ready
	 * ------------------------------------------------------------- */
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

})();
