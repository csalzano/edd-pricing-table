<?php
defined( 'ABSPATH' ) or exit;

/**
 * Plugin Name: Easy Digital Downloads - Pricing Table
 * Plugin URI: https://entriestogooglesheet.com
 * Description: Provides a shortcode [edd_pricing_table] that produces pricing tables for EDD products
 * Version: 1.0.1
 * Author: Corey Salzano
 * Author URI: https://github.com/csalzano
 * Text Domain: edd-pricing-table
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

class Breakfast_Pricing_Table
{
	const SCRIPT_HANDLE = 'edd-pricing-table';
	const SHORTCODE = 'edd_pricing_table';

	public function add_hooks()
	{
		add_action( 'init', array( $this, 'create_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_script' ) );
	}

	public function create_shortcode()
	{
		add_shortcode( self::SHORTCODE, array( $this, 'content' ) );
	}

	public function register_script()
	{
		wp_register_style( self::SCRIPT_HANDLE, plugins_url( 'edd-pricing-table/style.min.css' ) );
	}

	public function content( $atts )
	{
		$atts = shortcode_atts(
			array(
				'ids' => '',
			), $atts, self::SHORTCODE
		);

		if( empty( $atts['ids'] ) )
		{
			return '';
		}

		//ids is comma-delimited download IDs
		$ids = explode( ',', $atts['ids'] );
		if( empty( $ids ) )
		{
			return '';
		}

		wp_enqueue_style( self::SCRIPT_HANDLE );

		$html = '<div class="edd-pricing-table"><div class="planContainer">';

		foreach( $ids as $post_id )
		{
			$variable_pricing = get_post_meta( $post_id, 'edd_variable_prices', true );
			if( empty( $variable_pricing ) )
			{
				continue;
			}

			//Each variable price becomes a plan/price block
			/*
			Array
			(
				[1] => Array
					(
						[index] => 1
						[name] => Single Site
						[amount] => 79.00
						[license_limit] => 2
						[is_lifetime] => 1
					)

				[2] => Array
					(
						[index] => 2
						[name] => Unlimited Sites
						[amount] => 399.00
						[license_limit] => 0
						[is_lifetime] => 1
					)

			)
			*/
			foreach( $variable_pricing as $plan )
			{
				$html .= '<div class="plan">
							<div class="titleContainer">
								<div class="title">' . $plan['name'] . '</div>
							</div>
							<div class="infoContainer">
								<div class="price">
									<p>$' . $this->remove_cents( $plan['amount'] ) . '</p>
								</div>';
				
				if( ! empty( $plan['description'] ) )
				{
					//are the contents JSON? 
					$desc_obj = json_decode( $plan['description'] );
					if( ! empty( $desc_obj ) )
					{
						//Probably. Assume this format I invented:
						/*
							{
								"description": "Great for single sites & projects for your clients",
								"features":
								[
									"<b>1</b> Site",
									"<b>+1</b> Activation For Development",
									"<b>14</b> Days of Support",
									"<b>Lifetime</b> License Never Expires"
								]
							}
						*/
						$html .= sprintf( '<div class="p desc"><em>%s</em></div>', $desc_obj->description );
						
						if( ! empty( $desc_obj->features ) && is_array( $desc_obj->features ) )
						{
							$html .= '<ul class="features">';
							foreach( $desc_obj->features as $feature )
							{
								$html .= sprintf( '<li>%s</li>', $feature );
							}
							$html .= '</ul>';
						}
					}
					else
					{
						$html .= sprintf( '<div class="p desc"><em>%s</em></div>', $plan['description'] );
					}
				}

				//Buy button
				$html .= sprintf( 
					'<a class="selectPlan" href="%s">%s – $%s</a>',
					site_url( 'checkout?edd_action=add_to_cart&download_id=' . $post_id . '&edd_options[price_id]=' . $plan['index'] ),
					__( 'Buy', 'edd-pricing-table' ),
					$this->remove_cents( $plan['amount'] )
				);

				$html .= '</div></div>';
			}
		}

		$html .= '</div></div>';
		return $html;
	}

	private function remove_cents( $price )
	{
		return substr( $price, 0, strpos( $price, '.' ) );
	}
}
$pricing_table_293784234 = new Breakfast_Pricing_Table();
$pricing_table_293784234->add_hooks();
