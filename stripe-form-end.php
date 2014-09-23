<?php

if( !defined( 'ABSPATH' ) )
{
    exit;
} // Exit if accessed directly

if( !class_exists( 'DigLabs_Stripe_Shortcodes_Stripe_Form_End' ) )
{
    class DigLabs_Stripe_Shortcodes_Stripe_Form_End extends DigLabs_Stripe_Shortcodes_Abstract_Base
    {
        private $tag = "stripe_form_end";

        public function description()
        {
            return 'End a stripe payment form.';
        }

        public function options()
        {
            return array(
                'text' => array(
                    'type'        => 'string',
                    'description' => 'The text on the form\'s submit button. Defaults to <code>Submit</code>.',
                    'is_required' => false,
                    'example'     => 'text="Pay ME!"'
                )
            );
        }

        public function tag()
        {
            return parent::ShortCodeWithPrefix( $this->tag );
        }

        public function output( $atts, $content = null )
        {
            extract( shortcode_atts( array(
                                         "text" => "Submit"
                                     ), $atts ) );
            return $this->render_form_end( $text );
        }

        private function render_form_end( $text = null )
        {
            if( is_null( $text ) )
            {
                $text = __( 'Submit', DigLabs_Stripe_Payments::$localization_key );
            }
            return <<<HTML
<div class="stripe-payment-form-row-submit">
<button class="stripe-button-el" type="submit"><span>$text</span></button>
</div>
<div class="stripe-payment-form-row-progress">
<span class="stripe-payment-form-message"></span>
</div>
</form>
</div>
HTML;
        }


    }
}
