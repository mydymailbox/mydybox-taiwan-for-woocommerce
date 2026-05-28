<?php
namespace Mydybox\Tests\Unit\RuleEngine\Conditions;

use PHPUnit\Framework\TestCase;
use Mydybox\Rule_Engine\Conditions\Address;
use Mydybox\Rule_Engine\Context;
use ReflectionProperty;

class AddressTest extends TestCase {

	private Address $cond;

	protected function setUp(): void {
		$this->cond = new Address();
	}

	private function ctx( string $country = '', string $state = '' ): Context {
		$ctx  = new Context();
		$prop = new ReflectionProperty( Context::class, 'cache' );
		$prop->setAccessible( true );
		$prop->setValue( $ctx, [ 'shipping_country' => $country, 'shipping_state' => $state ] );
		return $ctx;
	}

	public function test_country_in_list_matches(): void {
		$this->assertTrue( $this->cond->matches(
			$this->ctx( 'TW' ),
			[ 'field' => 'country', 'op' => 'in', 'values' => [ 'TW', 'JP' ] ]
		) );
	}

	public function test_country_not_in_list_fails(): void {
		$this->assertFalse( $this->cond->matches(
			$this->ctx( 'US' ),
			[ 'field' => 'country', 'op' => 'in', 'values' => [ 'TW', 'JP' ] ]
		) );
	}

	public function test_not_in_op_inverts_result(): void {
		$this->assertTrue( $this->cond->matches(
			$this->ctx( 'US' ),
			[ 'field' => 'country', 'op' => 'not_in', 'values' => [ 'TW', 'JP' ] ]
		) );
	}

	public function test_state_field_matches_tw_state(): void {
		$this->assertTrue( $this->cond->matches(
			$this->ctx( 'TW', 'PIF' ),
			[ 'field' => 'state', 'op' => 'in', 'values' => [ 'PIF', 'PEN', 'KIN' ] ]
		) );
	}

	public function test_state_not_in_list_fails(): void {
		$this->assertFalse( $this->cond->matches(
			$this->ctx( 'TW', 'TPE' ),
			[ 'field' => 'state', 'op' => 'in', 'values' => [ 'PIF', 'PEN', 'KIN' ] ]
		) );
	}

	public function test_empty_values_list_fails_for_in(): void {
		$this->assertFalse( $this->cond->matches(
			$this->ctx( 'TW' ),
			[ 'field' => 'country', 'op' => 'in', 'values' => [] ]
		) );
	}
}
