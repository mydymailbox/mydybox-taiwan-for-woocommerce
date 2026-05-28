<?php
namespace Taiwan_Store_Core\Tests\Unit\RuleEngine;

use PHPUnit\Framework\TestCase;
use Taiwan_Store_Core\Rule_Engine\Context;
use ReflectionProperty;

class ContextTest extends TestCase {

	private function inject( Context $ctx, array $values ): void {
		$prop = new ReflectionProperty( Context::class, 'cache' );
		$prop->setAccessible( true );
		$prop->setValue( $ctx, $values );
	}

	public function test_cart_total_returns_injected_value(): void {
		$ctx = new Context();
		$this->inject( $ctx, [ 'cart_total' => 500.0 ] );
		$this->assertSame( 500.0, $ctx->cart_total() );
	}

	public function test_cart_total_returns_zero_without_wc(): void {
		$ctx = new Context();
		$this->assertSame( 0.0, $ctx->cart_total() );
	}

	public function test_shipping_country_returns_injected_value(): void {
		$ctx = new Context();
		$this->inject( $ctx, [ 'shipping_country' => 'TW' ] );
		$this->assertSame( 'TW', $ctx->shipping_country() );
	}

	public function test_shipping_state_returns_injected_value(): void {
		$ctx = new Context();
		$this->inject( $ctx, [ 'shipping_state' => 'TPE' ] );
		$this->assertSame( 'TPE', $ctx->shipping_state() );
	}

	public function test_product_ids_includes_adding_product(): void {
		$ctx = new Context();
		$this->inject( $ctx, [ 'product_ids' => [ 10, 20 ] ] );
		$ctx->set_adding_product( 99 );
		$this->assertContains( 99, $ctx->product_ids() );
		$this->assertContains( 10, $ctx->product_ids() );
	}

	public function test_set_adding_product_sets_qty(): void {
		$ctx = new Context();
		$ctx->set_adding_product( 5, 3 );
		$this->assertSame( 5, $ctx->adding_product_id() );
		$this->assertSame( 3, $ctx->adding_product_qty() );
	}

	public function test_chosen_shipping_from_package(): void {
		$ctx = new Context();
		$ctx->set_package( [
			'rates' => [
				'flat_rate:1' => new \stdClass(),
				'free_shipping:2' => new \stdClass(),
			],
		] );
		$methods = $ctx->chosen_shipping_methods();
		$this->assertContains( 'flat_rate:1', $methods );
		$this->assertContains( 'free_shipping:2', $methods );
	}
}
