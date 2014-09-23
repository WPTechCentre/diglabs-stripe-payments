<?php

if( !defined( 'ABSPATH' ) )
{
    exit;
} // Exit if accessed directly

if( !class_exists( 'DigLabs_Stripe_Shortcodes_Stripe_Form_Billing_Info' ) )
{
    class DigLabs_Stripe_Shortcodes_Stripe_Form_Billing_Info extends DigLabs_Stripe_Shortcodes_Abstract_Base
    {
        private $tag = "stripe_form_billing_info";

        private $fname;
        private $lname;
        private $email;

        public function description()
        {
            return "Creates a payment form section to collect billing information.";
        }

        public function options()
        {
            return array(
                'short'  => array(
                    'type'        => 'bool',
                    'description' => 'Collect the minimal data: <code>first name</code>, <code>last name</code>, <code>email</code>. <em>Caution: This level cannot be used when it is desired to collect taxes.</em>',
                    'is_required' => false,
                    'example'     => 'short=true'
                ),
                'medium' => array(
                    'type'        => 'string',
                    'description' => 'Collect a moderate amount of data: <code>first name</code>, <code>last name</code>, <code>email</code>, <code>state/province/region</code>, <code>country</code>',
                    'is_required' => false,
                    'example'     => 'medium=true'
                ),
                'long'   => array(
                    'type'        => 'string',
                    'description' => 'Collects the most data: <code>first name</code>, <code>last name</code>, <code>email</code>, <code>address line 1</code>, <code>address line 2</code>, <code>city</code>, <code>state/province/region</code>, <code>country</code>, <code>telephone number</code>',
                    'is_required' => false,
                    'example'     => 'long=true'
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
                                         "short"   => false,
                                         "medium"  => false,
                                         "state"   => 'AL',
                                         "country" => 'US',
                                     ), $atts ) );

            $style = $this->get_billing_style( $short, $medium );

            return $this->render_billing_info( $style, $state, $country );
        }

        public function render_billing_info( $style, $state, $country )
        {
            $this->fname = isset( $_REQUEST[ 'fname' ] ) ? $_REQUEST[ 'fname' ] : "";
            $this->lname = isset( $_REQUEST[ 'lname' ] ) ? $_REQUEST[ 'lname' ] : "";
            $this->email = isset( $_REQUEST[ 'email' ] ) ? $_REQUEST[ 'email' ] : "";


            switch( $style )
            {
                case "short":
                    return $this->render_short();
                case "medium":
                    return $this->render_medium( $address_1, $zip );
                default:
                    return $this->render_long( $state, $country );
            }
        }

        private function render_short()
        {
            return $this->render_name_email();
        }

		private function render_medium( $address_1, $zip )
        {
            $html = $this->render_name_email();
			$html .= $this->render_address_zip( $address_1, $zip );

            return $html;
        }

        private function render_long( $state, $country )
        {
            $html = $this->render_name_email();
            $html .= $this->render_address();
            $html .= $this->render_state_country( $state, $country );
            $html .= $this->render_zip();
            $html .= $this->render_phone();

            return $html;
        }

        private function render_name_email()
        {
            $billing_information = __( 'Billing Information', DigLabs_Stripe_Payments::$localization_key );
            $first_name          = __( 'First Name', DigLabs_Stripe_Payments::$localization_key );
            $last_name           = __( 'Last Name', DigLabs_Stripe_Payments::$localization_key );
            $email_address       = __( 'Email Address', DigLabs_Stripe_Payments::$localization_key );

            return <<<HTML
<div id="stripe-billing-info">
<div class="stripe-payment-form-row">
<label>$first_name</label>
<input type="text" size="20" maxlength="20" name="fname" class="fname required" value="$this->fname" placeholder="John" />
<span class="stripe-payment-form-error"></span>
</div>
<input type="hidden" name="lname" class="lname required" value="$this->lname" />
<div class="stripe-payment-form-row">
<label>$email_address</label>
<input type="text" size="20" maxlength="40" name="email" class="email email required" value="$this->email" placeholder="john@example.com" />
<span class="stripe-payment-form-error"></span>
</div>
HTML;
        }

        private function render_state_country( $state, $country )
        {
            $state_province = __( 'State/Province', DigLabs_Stripe_Payments::$localization_key );
            $country_str    = __( 'Country', DigLabs_Stripe_Payments::$localization_key );

            $base      = DigLabs_Stripe_Payments::GlobalInstance();
            $countries = file_get_contents( $base->paths->plugin() . '/classes/i18n/countries.json' );
            return <<<HTML
<script type="text/javascript">
var countries = $countries;
var defaultCountry = '$country';
var defaultState = '$state';
</script>
<div class="stripe-payment-form-row">
<label>$state_province</label>
<select name="state" class="state required"></select>
<input type="text" size="20" name="state" class="state required" />
<span class="stripe-payment-form-error"></span>
</div>
<div class="stripe-payment-form-row">
<label>$country_str</label>
<select name="country" class="country required"></select>
<span class="stripe-payment-form-error"></span>
</div>
HTML;
        }

        private function render_address()
        {
            $address_1 = __( 'Address 1', DigLabs_Stripe_Payments::$localization_key );
            $address_2 = __( 'Address 2', DigLabs_Stripe_Payments::$localization_key );
            $city      = __( 'City', DigLabs_Stripe_Payments::$localization_key );

            return <<<HTML
<div class="stripe-payment-form-row">
<label>$address_1</label>
<input type="text" size="20" name="address1" class="address1 required" />
<span class="stripe-payment-form-error"></span>
</div>
<div class="stripe-payment-form-row">
<label>$address_2</label>
<input type="text" size="20" name="address2" class="address2" />
<span class="stripe-payment-form-error"></span>
</div>
<div class="stripe-payment-form-row">
<label>$city</label>
<input type="text" size="20" name="city" class="city required" />
<span class="stripe-payment-form-error"></span>
</div>
HTML;
        }

        private function render_zip()
        {
            $zip_postal = __( 'Zip/Postal Code', DigLabs_Stripe_Payments::$localization_key );

            return <<<HTML
<div class="stripe-payment-form-row">
<label>$zip_postal</label>
<input type="text" size="20" name="zip" class="zip required" />
<span class="stripe-payment-form-error"></span>
</div>
HTML;
        }
        
		private function render_address_zip() // for WPTechCentre
        {
            $address_1 = __( 'Billing address', DigLabs_Stripe_Payments::$localization_key );
			$zip_postal = __( 'Post code', DigLabs_Stripe_Payments::$localization_key );

            return <<<HTML
<div class="stripe-payment-form-row">
<label>$address_1</label>
<input type="text" size="20" maxlength="60" name="address1" class="address1 required" placeholder="Building name/number" />
<span class="stripe-payment-form-error"></span>
</div>
<div class="stripe-payment-form-row">
<label>$zip_postal</label>
<input type="text" size="20" maxlength="8" name="zip" class="zip required" placeholder="Post code" />
<span class="stripe-payment-form-error"></span>
</div>
</div>
HTML;
        }

        private function render_phone()
        {
            $phone = __( 'Phone', DigLabs_Stripe_Payments::$localization_key );

            return <<<HTML
<div class="stripe-payment-form-row">
<label>$phone</label>
<input type="text" size="20" name="phone" />
<span class="stripe-payment-form-error"></span>
</div>
HTML;
        }
    }
}
