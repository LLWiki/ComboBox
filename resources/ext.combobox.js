"use strict";
const updateComboBox = function(widget, options, val) {
	if ( !options.includes( val ) ) { return; }
	const id = widget.$element.id;
	var $fields = widget.$element.parent().next( '.combobox-fieldset' ).children( '.combobox-field' );
	if ( id ) {
		$fields = $('.combobox-field[data-for]').filter(function() {
			return $(this).data( 'for' ) == id;
		}).add( $fields );
	}
	$fields.hide().filter(function() {
		return this.id == val;
	}).show();
};
mw.hook( 'wikipage.content' ).add(function($content) {
	$content.find( '.ext-combobox' ).each(function() {
		const options = $(this).find( 'option' ).toArray().map(function(ele) {
			return ele.value;
		}),
			widget = OO.ui.infuse( this, { menu: {
			filterFromInput: true,
			filterMode: 'substring'
		} } ).on('change', function(val) {
			updateComboBox( widget, options, val );
		}),
			$field = widget.$field;
		updateComboBox( widget, options, widget.getValue() );
		if ( $field ) {
			if ( widget.$element.hasClass( 'ext-combobox-text' ) ) {
				widget.getMenu().clearItems();
			} else {
				$field.find( '.oo-ui-buttonElement-button' ).click(function() {
					widget.setValue( '' ).getMenu().toggle( false ).toggle( true );
				});
			}
		}
	});
});
