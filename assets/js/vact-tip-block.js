/* global window */
(function (blocks, element, i18n, components, blockEditor) {
	'use strict';

	if (!blocks || !element || !i18n || !components) {
		return;
	}

	var el = element.createElement;
	var Fragment = element.Fragment;
	var __ = i18n.__;

	// Support both modern blockEditor and older editor APIs.
	var editor = blockEditor || window.wp.editor;
	if (!editor) {
		return;
	}

	var InspectorControls = editor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var RangeControl = components.RangeControl;

	// useBlockProps is the modern way to give the block a clickable wrapper in the editor.
	var useBlockProps = (blockEditor && blockEditor.useBlockProps) || (editor && editor.useBlockProps);

	// Server-side render component (modern and older fallback).
	var ServerSideRender = window.wp.serverSideRender || components.ServerSideRender;

	blocks.registerBlockType('vact/content-writing-tips', {
		title: __('Content Writing Tips', 'vact-content-writing-tips'),
		description: __(
			'Displays one or more centrally managed content writing tips.',
			'vact-content-writing-tips'
		),
		icon: 'welcome-learn-more',
		category: 'widgets',
		supports: {
			html: false,
		},
		attributes: {
			displayMode: {
				type: 'string',
				default: 'single',
			},
			tipCount: {
				type: 'number',
				default: 5,
			},
		},

		edit: function Edit(props) {
			var attrs = props.attributes || {};
			var mode = attrs.displayMode || 'single';
			var count = attrs.tipCount || 5;

			// Block wrapper props so the block is focusable/selectable in the editor.
			var blockProps = useBlockProps
				? useBlockProps({
						className: 'vact-tip-block',
				  })
				: { className: 'vact-tip-block' };

			var controls = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{
						title: __(
							'Content Writing Tip settings',
							'vact-content-writing-tips'
						),
						initialOpen: true,
					},
					[
						el(SelectControl, {
							label: __(
								'Display mode',
								'vact-content-writing-tips'
							),
							value: mode,
							options: [
								{
									label: __(
										'Single tip',
										'vact-content-writing-tips'
									),
									value: 'single',
								},
								{
									label: __(
										'Slider (multiple tips)',
										'vact-content-writing-tips'
									),
									value: 'slider',
								},
							],
							onChange: function (value) {
								props.setAttributes({
									displayMode: value,
								});
							},
						}),
						mode === 'slider' &&
							el(RangeControl, {
								label: __(
									'Number of tips to show',
									'vact-content-writing-tips'
								),
								min: 1,
								max: 20,
								value: count,
								onChange: function (value) {
									props.setAttributes({
										tipCount: value || 1,
									});
								},
							}),
					]
				)
			);

			var content;

			if (ServerSideRender) {
				// Show real rendered markup inside a proper block wrapper.
				content = el(
					'div',
					blockProps,
					el(ServerSideRender, {
						block: 'vact/content-writing-tips',
						attributes: props.attributes,
					})
				);
			} else {
				// Fallback: placeholder, still wrapped so it's selectable.
				content = el(
					'div',
					blockProps,
					el(
						'p',
						{ className: 'vact-tip__placeholder' },
						mode === 'slider'
							? __(
									'A slider of content writing tips will render here on the front end.',
									'vact-content-writing-tips'
							  )
							: __(
									'A single content writing tip will render here on the front end.',
									'vact-content-writing-tips'
							  )
					)
				);
			}

			// Return controls + block content.
			return el(
				Fragment,
				{},
				[
					controls,
					content,
				]
			);
		},

		// Dynamic block: markup rendered in PHP.
		save: function Save() {
			return null;
		},
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.i18n,
	window.wp.components,
	window.wp.blockEditor
);