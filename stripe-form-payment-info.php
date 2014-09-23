<?php

if( !defined( 'ABSPATH' ) )
{
    exit;
} // Exit if accessed directly

if( !class_exists( 'DigLabs_Stripe_Shortcodes_Stripe_Form_Payment_Info' ) )
{
    class DigLabs_Stripe_Shortcodes_Stripe_Form_Payment_Info extends DigLabs_Stripe_Shortcodes_Abstract_Base
    {
        private $tag = "stripe_form_payment_info";

        public function description()
        {
            return 'Creates a payment form section to collect card information (card number, cvc, expiration date).';
        }

        public function options()
        {
            return array();
        }

        public function tag()
        {
            return parent::ShortCodeWithPrefix( $this->tag );
        }

        public function output( $atts, $content = null )
        {
            extract( shortcode_atts( array(), $atts ) );

            return $this->render();
        }

        public function render()
        {
            $base      = DigLabs_Stripe_Payments::GlobalInstance();
            $image_url = $base->urls->plugin() . '/images/types.png';

            $current_month = intval( date( 'm' ) );
            $months        = "";
            for( $i = 1; $i <= 12; $i++ )
            {
                $val = $i < 10 ? '0' . $i : $i;
                $sel = ( $i == $current_month ) ? "selected" : "";
                $months .= "<option value='$val' $sel>$val</option>";
            }

            $current_year = intval( date( 'Y' ) );
            $years        = "";
            for( $i = 0; $i < 12; $i++ )
            {
                $val = $current_year + $i;
                $sel = ( $i == 0 ) ? 'selected' : '';
                $years .= "<option value='$val' $sel>$val</option>";
            }

            $payment_information = __( 'Payment Information', DigLabs_Stripe_Payments::$localization_key );
            $card_number         = __( 'Card Number', DigLabs_Stripe_Payments::$localization_key );
            $cvc                 = __( 'CVC', DigLabs_Stripe_Payments::$localization_key );
            $expiration          = __( 'Expiration', DigLabs_Stripe_Payments::$localization_key );

            return <<<HTML
<div id="stripe-payment-info">
    <div class="stripe-payment-form-row">
        <label>$card_number</label>
        <input type="text" size="20" maxlength="16" class="cardNumber number required stripe-sensitive" placeholder="16 digit card number" />
        <span class="stripe-payment-form-error"></span>
    </div>
    <div class="stripe-payment-form-row">
        <label>$cvc</label>
        <input type="text" size="4" maxlength="3" class="cardCvc number required stripe-sensitive" placeholder="3 digit CVC code" />
        <span class="stripe-payment-form-error"></span>
    </div>
    <div class="stripe-payment-form-row">
        <label id="stripe-expiry">$expiration</label>
        <input type="text" size="4" maxlength="2" class="cardExpiryMonth required card-expiry-month stripe-sensitive" placeholder="MM" />
        /
        <input type="text" size="6" maxlength="4" class="cardExpiryYear required card-expiry-year stripe-sensitive" placeholder="YY" />
        <span class="stripe-payment-form-error"></span>
    </div>
</div>
HTML;
        }
    }
}
