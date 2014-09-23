<?php

if( !defined( 'ABSPATH' ) )
{
    exit;
} // Exit if accessed directly

if( !class_exists( 'DigLabs_Stripe_Shortcodes_Stripe_Form_Plan_Info' ) )
{
    class DigLabs_Stripe_Shortcodes_Stripe_Form_Plan_Info extends DigLabs_Stripe_Shortcodes_Abstract_Base
    {
        private $tag = "stripe_form_plan_info";

        public function description()
        {
            return 'Creates a section in the form to collect a recurring payment.';
        }

        public function options()
        {
            return array(
                'plan' => array(
                    'type'        => 'string',
                    'description' => 'The <code>ID</code> of a plan that exists in your Stripe.com account. If this attribute is not provided, the payment form will be generated as a single payment with the amount not specified.',
                    'is_required' => true,
                    'example'     => 'plan="monthly_49"'
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
                                         "plan" => null
                                     ), $atts ) );

            if( $plan == null && isset( $_REQUEST[ 'plan' ] ) )
            {
                $plan = $_REQUEST[ 'plan' ];
            }

            if( $plan == null )
            {
                return "No plan ID found. Expected the following format: <code>[stripe_form_plan_info plan='PLAN_ID']</code>";
            }

            return $this->render_plan_info( $plan );
        }

        public function render_plan_info( $plan = null )
        {
            $plans = explode( ',', $plan );

            // Add this to the users session and a hash to the payment form.
            //
            $html = "";
            $base = DigLabs_Stripe_Payments::GlobalInstance();
            if(!$base->is_form_id_rendered)
            {
                $form_data = new DigLabs_Stripe_Helpers_Payment_Form_Data();
                $html .= $form_data->render_form_id($plans, DigLabs_Stripe_Handlers_Ajax_Processor_Recurring::$type);
            }

            if( count( $plans ) == 1 && strtolower( $plans[ 0 ] ) != 'other' )
            {
                $html .= $this->render_single_plan_info( $plans[ 0 ] );
            }
            else
            {
                $html .= $this->render_multiple_plan_info( $plans );
            }
            return $html;
        }

        public function render_single_plan_info( $plan )
        {
            try
            {
                $planInfo       = Stripe_Plan::retrieve( $plan );
            }
            catch(Exception $e)
            {
                return "Plan with ID='$plan' does not exist.";
            }
            $amount         = $planInfo->amount;
            $currency       = strtoupper($planInfo->currency);
            $planName       = $planInfo->name;
            $interval_count = $planInfo->interval_count;
            $interval       = $interval_count . ' ' . $planInfo->interval;
            if( $interval_count > 1 )
            {
                $interval .= 's';
            }

            $country_helper = new DigLabs_Stripe_I18N_Country_Helper();
            $country        = $country_helper->country_from_currency_iso($currency);
            $amountShown    = $amount/100;

            $plan_information = __( 'Plan Information', DigLabs_Stripe_Payments::$localization_key );
            $plan_name        = __( 'Plan Name', DigLabs_Stripe_Payments::$localization_key );
            $plan_amount      = __( 'Amount', DigLabs_Stripe_Payments::$localization_key );
            $every            = __( 'Every', DigLabs_Stripe_Payments::$localization_key );

            return <<<HTML
<input type="hidden" name="plan-option" value="0" />
<input class="plan" type="hidden" name="plan" value="$plan" />
<input class="amount" type="hidden" name="amount" value="$amount" />
<input class="interval" type="hidden" name="interval" value="$interval" />
<h2>Start my &pound;$amountShown $planName monthly subscription</h2>
<p>Enter your credit or debit card details to start your monthly subscription.</p>
<p>You will be charged &pound;$amountShown today, then &pound;$amountShown on the same day every month until you cancel your subscription.</p>
<p>You can upgrade, downgrade or cancel any time - just let us know <img src="//wptechcentre.com/wp-includes/images/smilies/icon_smile.gif" alt=":)" class="wp-smiley"></p>
<p></p>
HTML;
        }

        public function render_multiple_plan_info( $plans )
        {
            $settings       = new DigLabs_Stripe_Helpers_Settings();
            $country_iso    = $settings->getCountryIso();
            $country_helper = new DigLabs_Stripe_I18N_Country_Helper();
            $country        = $country_helper->country( $country_iso );
            $currency       = $country->currency_name;

            // Build the HTML;
            //
            $plan_information = __( 'Plan Information', DigLabs_Stripe_Payments::$localization_key );
            $html             = <<<HTML
<div class="stripe-payment-plans">
<h3 class="stripe-payment-form-section">$plan_information</h3>
<div class="stripe-payment-form-row">
HTML;
            $disabled         = '';
            if( count( $plans ) > 1 )
            {
                $plan_options = __( 'Plan Options', DigLabs_Stripe_Payments::$localization_key );
                $disabled     = 'disabled="disabled"';
                $html .= <<<HTML
<label>$plan_options</label>
<select class="diglabs-plan" name="plan-option" style="width:auto;">
HTML;

                foreach( $plans as $id => $plan_id )
                {
                    $option = "<option value='$id' ";
                    if( strtolower( $plan_id ) == "other" )
                    {
                        $option .= "data-amount=''";
                        $option .= ">";
                        $option .= $plan_id;
                    }
                    else
                    {
                        $stripe_plan_id = trim( $plan_id );
                        try
                        {
                            $plan = Stripe_Plan::retrieve( $stripe_plan_id );
                        }
                        catch( Exception $e)
                        {
                            $plan = null;
                        }

                        if(!is_null($plan))
                        {
                            $amount         = $plan->amount/100;
                            $currency       = strtoupper($plan->currency);
                            $country        = $country_helper->country_from_currency_iso($currency);
                            $amount_str     = $country_helper->currency($amount, $country->country_iso_2char);
                            $option .= "data-amount='" . $amount_str . "' ";
                            $option .= "data-count='" . $plan->interval_count . "' ";
                            $option .= "data-interval='" . $plan->interval . "'";
                            $option .= ">";
                            $option .= $plan->name;
                        }
                        else
                        {
                            $option .= ">Plan with ID='$stripe_plan_id' does not exist.";
                        }
                    }
                    $option .= "</option>";
                    $html .= $option;
                }

                $html .= <<<HTML
</select>
HTML;
            }
            else
            {
                $html .= <<<HTML
<input name="plan" type="hidden" value="other" />
HTML;
            }
            $every  = __( 'Every', DigLabs_Stripe_Payments::$localization_key );
            $weeks  = __( 'Week(s)', DigLabs_Stripe_Payments::$localization_key );
            $months = __( 'Month(s)', DigLabs_Stripe_Payments::$localization_key );
            $years  = __( 'Year', DigLabs_Stripe_Payments::$localization_key );
            $plan_amount      = __( 'Amount', DigLabs_Stripe_Payments::$localization_key );

            $plugin = DigLabs_Stripe_Payments::GlobalInstance();
            $js_url = $plugin->urls->plugin() . '/js/plans.js';

            $html .= <<<HTML
<input class="amount" type="hidden" name="amount" />
<input class="interval" type="hidden" name="interval" />
</div>
<div class="stripe-payment-form-row">
<label>$plan_amount</label>
<input type="text" size="20" name="amountString" $disabled class="amountString disabled required" />
<span class="stripe-payment-form-error"></span>
</div>
<div class="stripe-payment-form-row">
<label>$every</label>
<select name="planCount" $disabled class="planCount disabled required stripe-payment-form-small">
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
    <option value="4">4</option>
    <option value="5">5</option>
    <option value="6">6</option>
    <option value="7">7</option>
    <option value="8">8</option>
    <option value="9">9</option>
    <option value="10">10</option>
    <option value="11">11</option>
    <option value="12">12</option>
</select>
<select name="planInterval" $disabled class="planInterval disabled stripe-payment-form-medium">
    <option value="week">$weeks</option>
    <option value="month">$months</option>
    <option value="year">$years</option>
</select>
<span class="stripe-payment-form-error"></span>
</div>
</div>
<script type="text/javascript" src="$js_url"></script>
HTML;
            return $html;
        }
    }
}
