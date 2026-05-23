import '../css/dashkit.css';
import 'toastle/style.css';
import Toastle from 'toastle';
import { Chart } from 'chart.js/auto';

const REST = dashkitConfig.restUrl;
const NONCE = dashkitConfig.nonce;
const I18N = dashkitConfig.i18n;

// ════════════════════════════════════════════════════════════════
// 1. TOAST SYSTEM
// ════════════════════════════════════════════════════════════════

function toast( opts ) {
	Toastle( { text: opts.message, type: opts.type || 'info', duration: opts.duration } );
}

window.DashkitToast = { show: toast };

// ════════════════════════════════════════════════════════════════
// 2. ACTION PIPELINE
// ════════════════════════════════════════════════════════════════

async function handleAction( btn ) {
	const rowId = btn.dataset.rowId;
	const after = btn.dataset.after || 'notify';
	const confirmNeeded = btn.dataset.confirm === 'true';
	const endpointUrl = btn.dataset.endpointUrl || '';
	const method = ( btn.dataset.method || 'POST' ).toUpperCase();
	const table = btn.closest( 'table' );

	if ( ! endpointUrl ) {
		console.error( 'Dashkit: data-endpoint-url is required on action buttons.' );
		return;
	}

	if ( confirmNeeded && ! window.confirm( I18N.confirmAction ) ) {
		return;
	}

	btn.disabled = true;
	btn.classList.add( 'dashkit-action--loading' );

	try {
		const response = await fetch( endpointUrl, {
			method,
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
			body: JSON.stringify( { id: String( rowId ) } ),
		} );

		const result = await response.json();

		if ( ! response.ok || result.success === false ) {
			toast( { type: 'error', message: result.message || I18N.actionFailed } );
			return;
		}

		toast( { type: result.type || 'success', message: result.message || I18N.actionDone } );

		const resolvedAfter = result.after || after;
		if ( resolvedAfter === 'reload' ) {
			reloadWidget( table.closest( '.dashkit-widget' ) );
		}
	} catch ( err ) {
		toast( { type: 'error', message: I18N.requestFailed + ' ' + err.message } );
	} finally {
		btn.disabled = false;
		btn.classList.remove( 'dashkit-action--loading' );
	}
}

document.addEventListener( 'click', function ( e ) {
	const btn = e.target.closest( '[data-dashkit-action]' );
	if ( btn ) {
		e.preventDefault();
		handleAction( btn );
	}
} );

// ════════════════════════════════════════════════════════════════
// 3. ASYNC WIDGET RELOAD
// ════════════════════════════════════════════════════════════════

function refreshChart( canvas, newData ) {
	const instance = Chart.getChart( canvas );
	if ( ! instance ) return;
	const chartType = canvas.dataset.chartType || 'bar';
	const lineColor = canvas.dataset.lineColor || '';
	newData.datasets = applyColours( newData.datasets, lineColor, chartType );
	instance.data = newData;
	instance.update();
}

function reloadWidget( widget ) {
	const url = widget.dataset.asyncUrl;
	if ( ! url ) {
		return;
	}

	widget.classList.add( 'dashkit-widget--loading' );

	fetch( url, { headers: { 'X-WP-Nonce': NONCE } } )
		.then( ( r ) => r.json() )
		.then( ( data ) => {
			if ( data.rows ) {
				const table = widget.querySelector( 'table' );
				if ( table ) rebuildTableBody( table, data.rows, data.columns, data.has_actions );
			}
			if ( data.chart_data ) {
				const canvas = widget.querySelector( 'canvas[data-chart-type]' );
				if ( canvas ) refreshChart( canvas, data.chart_data );
			}
			widget.classList.remove( 'dashkit-widget--loading' );
		} )
		.catch( () => {
			widget.classList.remove( 'dashkit-widget--loading' );
		} );
}

function setDisplay( tableWidget, selector, value ) {
	const el = tableWidget.querySelector( selector );
	if ( el ) el.style.display = value;
}

function rebuildTableBody( table, rows, columns, hasActions ) {
	const tableWidget = table.closest( '.dashkit-table-widget' );
	const tbody = table.querySelector( 'tbody' );
	tbody.innerHTML = '';

	if ( ! rows.length ) {
		setDisplay( tableWidget, '.dashkit-table__wrap', 'none' );
		setDisplay( tableWidget, '.dashkit-table__toolbar', 'none' );
		return;
	}

	setDisplay( tableWidget, '.dashkit-table__empty-state', 'none' );
	setDisplay( tableWidget, '.dashkit-table__wrap', '' );
	setDisplay( tableWidget, '.dashkit-table__toolbar', '' );

	rows.forEach( function ( row ) {
		const tr = document.createElement( 'tr' );
		tr.dataset.rowId = row._id || '';
		columns.forEach( function ( col ) {
			const td = document.createElement( 'td' );
			td.dataset.col = col.key;
			td.innerHTML = row[ col.key ] ?? '';
			tr.append( td );
		} );
		if ( hasActions && row._actions_html ) {
			const td = document.createElement( 'td' );
			td.className = 'dashkit-table__actions';
			td.innerHTML = row._actions_html;
			tr.append( td );
		}
		tbody.append( tr );
	} );

	const counter = tableWidget.querySelector( '[data-dashkit-row-count]' );
	if ( counter ) counter.textContent = rows.length + ' ' + I18N.items;
}

// ════════════════════════════════════════════════════════════════
// 4. OPTIONS PANEL
// ════════════════════════════════════════════════════════════════

function buildOptionsPanel( panel ) {
	const schema = JSON.parse( panel.dataset.schema || '[]' );
	const options = JSON.parse( panel.dataset.options || '{}' );
	const widgetId = panel.dataset.widgetId;
	const pageSlug = panel.dataset.pageSlug;

	const inner = document.createElement( 'div' );
	inner.className = 'dashkit-options-panel__inner';

	schema.forEach( function ( field ) {
		const row = document.createElement( 'div' );
		row.className = 'dashkit-options-panel__row';

		const label = document.createElement( 'label' );
		label.textContent = field.label;

		let ctrl;

		switch ( field.type ) {
			case 'text':
			case 'number': {
				ctrl = document.createElement( 'input' );
				ctrl.type = field.type;
				ctrl.value = options[ field.key ] ?? '';
				ctrl.dataset.key = field.key;
				ctrl.className = 'regular-text dashkit-ctrl';
				break;
			}

			case 'select': {
				ctrl = document.createElement( 'select' );
				ctrl.dataset.key = field.key;
				ctrl.className = 'dashkit-ctrl';
				( field.choices || [] ).forEach( function ( c ) {
					const val = c !== null && typeof c === 'object' ? c.value : c;
					const lbl = c !== null && typeof c === 'object' ? c.label : c;
					const opt = document.createElement( 'option' );
					opt.value = val;
					opt.textContent = lbl;
					// eslint-disable-next-line eqeqeq
					opt.selected = options[ field.key ] == val;
					ctrl.append( opt );
				} );
				break;
			}

			case 'checkbox': {
				ctrl = document.createElement( 'input' );
				ctrl.type = 'checkbox';
				ctrl.dataset.key = field.key;
				ctrl.checked = !! options[ field.key ];
				ctrl.className = 'dashkit-ctrl';
				break;
			}

			case 'toggle': {
				ctrl = document.createElement( 'input' );
				ctrl.type = 'checkbox';
				ctrl.dataset.key = field.key;
				ctrl.checked = !! options[ field.key ];
				ctrl.className = 'dashkit-ctrl dashkit-ctrl--toggle';
				break;
			}

			case 'radio': {
				ctrl = document.createElement( 'div' );
				ctrl.className = 'dashkit-radio';
				( field.choices || [] ).forEach( function ( choice ) {
					const itemLabel = document.createElement( 'label' );
					itemLabel.className = 'dashkit-radio__item';
					const radio = document.createElement( 'input' );
					radio.type = 'radio';
					radio.name = widgetId + '-' + field.key;
					radio.value = choice.value;
					radio.dataset.key = field.key;
					// eslint-disable-next-line eqeqeq
					radio.checked = options[ field.key ] == choice.value;
					radio.className = 'dashkit-ctrl';
					const span = document.createElement( 'span' );
					span.textContent = choice.label;
					itemLabel.append( radio, span );
					ctrl.append( itemLabel );
				} );
				break;
			}

			case 'buttonset': {
				ctrl = document.createElement( 'div' );
				ctrl.className = 'dashkit-buttonset';
				( field.choices || [] ).forEach( function ( choice ) {
					const itemLabel = document.createElement( 'label' );
					itemLabel.className = 'dashkit-buttonset__item';
					const radio = document.createElement( 'input' );
					radio.type = 'radio';
					radio.name = widgetId + '-' + field.key;
					radio.value = choice.value;
					radio.dataset.key = field.key;
					// eslint-disable-next-line eqeqeq
					radio.checked = options[ field.key ] == choice.value;
					radio.className = 'dashkit-ctrl';
					const span = document.createElement( 'span' );
					span.textContent = choice.label;
					itemLabel.append( radio, span );
					ctrl.append( itemLabel );
				} );
				break;
			}

			case 'multicheck': {
				ctrl = document.createElement( 'div' );
				ctrl.className = 'dashkit-multicheck';
				const selected = options[ field.key ] || [];
				( field.choices || [] ).forEach( function ( choice ) {
					const itemLabel = document.createElement( 'label' );
					itemLabel.className = 'dashkit-multicheck__item';
					const checkbox = document.createElement( 'input' );
					checkbox.type = 'checkbox';
					checkbox.value = choice.value;
					checkbox.checked = selected.includes( choice.value );
					checkbox.dataset.key = field.key;
					checkbox.className = 'dashkit-ctrl dashkit-ctrl--multicheck';
					const span = document.createElement( 'span' );
					span.textContent = choice.label;
					itemLabel.append( checkbox, span );
					ctrl.append( itemLabel );
				} );
				break;
			}

			default: {
				ctrl = document.createElement( 'span' );
				ctrl.textContent = '(unsupported: ' + field.type + ')';
			}
		}

		row.append( label, ctrl );
		inner.append( row );
	} );

	const footer = document.createElement( 'div' );
	footer.className = 'dashkit-options-panel__footer';

	const saveBtn = document.createElement( 'button' );
	saveBtn.className = 'button button-primary dashkit-options-panel__save';
	saveBtn.textContent = I18N.saveOptions;

	const status = document.createElement( 'span' );
	status.className = 'dashkit-options-panel__status';

	footer.append( saveBtn, status );
	inner.append( footer );
	panel.append( inner );

	saveBtn.addEventListener( 'click', function ( e ) {
		e.preventDefault();
		const newOpts = collectOptions( panel, schema );

		status.textContent = I18N.saving;
		status.className = 'dashkit-options-panel__status';

		fetch( REST + '/options', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
			body: JSON.stringify( { widget_id: widgetId, page_slug: pageSlug, options: newOpts } ),
		} )
			.then( ( r ) => r.json() )
			.then( ( data ) => {
				if ( data.success ) {
					status.textContent = I18N.saved;
					status.className = 'dashkit-options-panel__status is-success';
					setTimeout( () => {
						status.textContent = '';
						status.className = 'dashkit-options-panel__status';
					}, 2500 );
					const widget = panel.closest( '.dashkit-widget' );
					widget.dispatchEvent(
						new CustomEvent( 'dashkit:options:saved', {
							bubbles: true,
							detail: newOpts,
						} )
					);
					toast( { type: 'success', message: I18N.optionsSaved, duration: 2500 } );
					reloadWidget( widget );
				} else {
					throw new Error( data.message || I18N.saveFailed );
				}
			} )
			.catch( ( err ) => {
				status.textContent = I18N.saveError;
				status.className = 'dashkit-options-panel__status is-error';
				toast( { type: 'error', message: err.message } );
			} );
	} );
}

function collectOptions( panel, schema ) {
	const out = {};
	schema.forEach( function ( field ) {
		switch ( field.type ) {
			case 'checkbox':
			case 'toggle':
				out[ field.key ] =
					panel.querySelector( `[data-key="${ field.key }"]` ).checked === true;
				break;
			case 'radio':
			case 'buttonset': {
				const checked = panel.querySelector( `[data-key="${ field.key }"]:checked` );
				out[ field.key ] = checked ? checked.value : '';
				break;
			}
			case 'multicheck':
				out[ field.key ] = Array.from(
					panel.querySelectorAll( `[data-key="${ field.key }"]:checked` )
				).map( ( el ) => el.value );
				break;
			case 'number':
				out[ field.key ] = parseFloat(
					panel.querySelector( `[data-key="${ field.key }"]` ).value
				);
				break;
			default:
				out[ field.key ] = panel.querySelector( `[data-key="${ field.key }"]` ).value;
		}
	} );
	return out;
}

document.addEventListener( 'click', function ( e ) {
	const toggle = e.target.closest( '[data-dashkit-options-toggle]' );
	if ( toggle ) {
		toggle
			.closest( '.dashkit-widget' )
			.querySelector( '.dashkit-options-panel' )
			.classList.toggle( 'is-open' );
	}
} );

// ════════════════════════════════════════════════════════════════
// 5. CHART WIDGET
// ════════════════════════════════════════════════════════════════

const barValuePlugin = {
	id: 'dashkitBarValues',
	afterDatasetsDraw( chart ) {
		const ctx = chart.ctx;
		chart.data.datasets.forEach( ( dataset, i ) => {
			chart.getDatasetMeta( i ).data.forEach( ( bar, index ) => {
				const value = dataset.data[ index ];
				if ( value === null || value === undefined ) return;
				ctx.save();
				ctx.fillStyle = '#1d2327';
				ctx.font = '11px sans-serif';
				ctx.textAlign = 'center';
				ctx.textBaseline = 'bottom';
				ctx.fillText( value, bar.x, bar.y - 8 );
				ctx.restore();
			} );
		} );
	},
};

function applyColours( datasets, lineColor, chartType ) {
	const color = lineColor || '#6facde';
	return datasets.map( ( ds ) => {
		if ( chartType === 'bar' ) {
			return { ...ds, backgroundColor: color + 'cc', borderColor: color, borderWidth: 1 };
		}
		return {
			...ds,
			borderColor: color,
			backgroundColor: color + '22',
			borderWidth: 2,
			pointBackgroundColor: color,
			pointRadius: 4,
			tension: 0.3,
			fill: false,
		};
	} );
}

function initChart( canvas ) {
	const chartType = canvas.dataset.chartType || 'bar';
	const lineColor = canvas.dataset.lineColor || '';
	let data;

	try {
		data = JSON.parse( canvas.dataset.chartData || '{}' );
	} catch {
		console.error( 'Dashkit Chart: invalid data JSON on canvas', canvas.id );
		return;
	}

	const xLabel = canvas.dataset.axisXLabel || '';
	const yLabel = canvas.dataset.axisYLabel || '';

	data.datasets = applyColours( data.datasets, lineColor, chartType );

	new Chart( canvas, {
		type: chartType,
		data,
		plugins: chartType === 'bar' ? [ barValuePlugin ] : [],
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { display: data.datasets.length > 1 },
				tooltip: {
					displayColors: false,
					backgroundColor: 'rgba(29,35,39,0.85)',
					padding: 6,
					callbacks: {
						label: ( ctx ) => String( ctx.parsed.y ),
					},
				},
			},
			scales: {
				x: {
					title: { display: !! xLabel, text: xLabel },
				},
				y: {
					beginAtZero: true,
					title: { display: !! yLabel, text: yLabel },
				},
			},
		},
	} );
}

// ════════════════════════════════════════════════════════════════
// INIT
// ════════════════════════════════════════════════════════════════

document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.dashkit-options-panel' ).forEach( buildOptionsPanel );
	document.querySelectorAll( '[data-lazy="1"]' ).forEach( reloadWidget );
	document.querySelectorAll( 'canvas[data-chart-type]' ).forEach( initChart );
} );
