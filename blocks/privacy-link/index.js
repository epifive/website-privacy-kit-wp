/**
 * Editor script for the "Privacy hub link" block (no build step: plain wp.* globals).
 * The front end is rendered in PHP (EpiFive_WPK_Frontend::render_block).
 */
( function ( blocks, element, blockEditor, components, i18n, serverSideRender ) {
	'use strict';
	var el = element.createElement;
	var __ = i18n.__;

	blocks.registerBlockType( 'epifive-wpk/privacy-link', {
		edit: function ( props ) {
			var attrs = props.attributes;
			return el(
				element.Fragment,
				null,
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'Privacy hub link', 'epifive-website-privacy-kit' ) },
						el( components.TextControl, {
							label: __( 'Link text', 'epifive-website-privacy-kit' ),
							value: attrs.text,
							placeholder: __( 'Privacy centre', 'epifive-website-privacy-kit' ),
							onChange: function ( value ) {
								props.setAttributes( { text: value } );
							},
						} ),
						el( components.ToggleControl, {
							label: __( 'Add a "Privacy choices" link', 'epifive-website-privacy-kit' ),
							checked: !! attrs.showChoices,
							onChange: function ( value ) {
								props.setAttributes( { showChoices: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockEditor.useBlockProps(),
					el( serverSideRender, { block: 'epifive-wpk/privacy-link', attributes: attrs } )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n, window.wp.serverSideRender );
