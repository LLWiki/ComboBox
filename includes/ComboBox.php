<?php
/**
 * This tag extension creates the <combobox> and <combooption> tags for creating OOUI ComboBox widgets on wiki pages.
 *
 * @section LICENSE
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

class ComboBox {
	/**
	 * Initiate the tags
	 * @param Parser $parser
	 * @return boolean true
	 */
	public static function init( $parser ) {
		$parser->comboboxData = array(
			'enabled' => false,
			'nested' => false, // Keeps track of whether the <combooption> is nested within a <combobox> or not.
			'options' => array() // Contains a list of the previously used option names in that scope.
		);
		$parser->setHook('combobox', array(new self(), 'renderBox'));
		$parser->setHook('combooption', array(new self(), 'renderOption'));
		return true;
	}

	/**
	 * Converts each <combooption> into an OOUI Select widget.
	 *
	 * @param string $input
	 * @param array $attr
	 * @param Parser $parser
	 * @return string
	 */
	public function renderOption($input, $attr = array(), $parser) {
		if ( !isset( $attr['name'] ) ) {
			return '<span class="error">'
				. wfMessage( 'combobox-option-without-name' )
				. '</span>';
		}
		$converter = $parser->getTargetLanguageConverter();
		$name = $converter->convert( $attr['name'] );
		$id = Sanitizer::safeEncodeAttribute( $name );
		if ( $parser->comboboxData['nested'] === true ) {
			$options = &$parser->comboboxData['options'];
			if ( !in_array( $id, $options ) ) {
				$options[$name] = $id;
			}
		} elseif ( !isset( $attr['for'] ) ) {
			return '<span class="error">'
				. wfMessage( 'combobox-option-without-for' )
				. '</span>';
		}
		if ( !isset( $input ) || $input === '' ) {
			return '';
		}
		$tag = isset( $attr['inline'] ) ? 'span' : 'div';
		$safeAttr = Sanitizer::validateTagAttributes( $attr, 'div' );
		$safeAttr['id'] = $id;
		$safeAttr['class'] = 'combobox-field ' . ( $safeAttr['class'] ?? '' );
		if ( isset( $attr['for'] ) ) {
			$safeAttr['data-for'] = Sanitizer::safeEncodeAttribute( $attr['for'] );
		}
		return "<$tag "
			. Sanitizer::safeEncodeTagAttributes( $safeAttr )
			. '>'
			. $parser->recursiveTagParse( $input )
			. "</$tag>";
	}

	/**
	 * Converts each <combobox> tag to an OOUI ComboBox widget.
	 *
	 * @param string $input
	 * @param array $attr
	 * @param Parser $parser
	 * @return string
	 */
	public function renderBox($input, $attr = array(), $parser) {
		if ( $parser->comboboxData['nested'] !== false || !isset( $input ) ) {
			return ''; // Exit if the tag is self-closing. <combobox> is a container element, so should always have something in it.
		}
		if ( $parser->comboboxData['enabled'] === false ) {
			$parser->comboboxData['enabled'] = true;
			$parser->enableOOUI();
			$pout = $parser->getOutput();
			$pout->addModules( [ 'ext.combobox' ] );
			$pout->addModuleStyles( [ 'ext.combobox.styles' ] );
		}
		$parser->comboboxData['options'] = array();
		$parser->comboboxData['nested'] = true;
		$converter = $parser->getTargetLanguageConverter();
		$placeholder = isset( $attr['placeholder'] ) ?
			$converter->convert( $attr['placeholder'] ) :
			'';
		$value = isset( $attr['value'] ) ?
			$converter->convert( $attr['value'] ) :
			'';
		$id = isset( $attr['id'] ) ?
			Sanitizer::safeEncodeAttribute( $attr['id'] ) :
			'';
		$classes = isset( $attr['class'] ) ?
			explode(' ', Sanitizer::escapeClass( $attr['class'] )) :
			array();
		$classes[] = 'ext-combobox';
		if ( isset( $attr['text'] ) && !isset( $attr['dropdown'] ) ) {
			$classes[] = 'ext-combobox-text';
		}
		$newstr = $parser->recursiveTagParse( $input );
		$config = array(
			'infusable' => true,
			'id' => $id,
			'placeholder' => $placeholder,
			'value' => $value,
			'classes' => $classes,
			'options' => Xml::listDropDownOptionsOoui( $parser->comboboxData['options'] )
		);
		if ( isset( $attr['dropdown'] ) ) {
			$form = new OOUI\DropdownInputWidget( $config );
		} else {
			$form = new OOUI\ComboBoxInputWidget( $config );
		}
		$parser->comboboxData['nested'] = false;
		$css = isset( $attr['style'] ) ?
			' style="' . htmlspecialchars( Sanitizer::checkCSS( $attr['style'] ) ) . '"':
			'';
		return "<div class=\"combobox-container\"$css>" . $form->toString()
			. "</div><div class=\"combobox-fieldset\">$newstr</div>";
	}
}
