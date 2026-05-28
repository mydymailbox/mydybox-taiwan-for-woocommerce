<?php
namespace Mydyma_TCS\Tests\Unit\RuleEngine\Conditions;

use PHPUnit\Framework\TestCase;
use Mydyma_TCS\Rule_Engine\Conditions\Cart_Total;
use Mydyma_TCS\Rule_Engine\Context;
use ReflectionProperty;

class CartTotalTest extends TestCase {

	private Cart_Total $cond;

	protected function setUp(): void {
		$this->cond = new Cart_Total();
	}

	private function ctx( float $total ): Context {
		$ctx  = new Context();
		$prop = new ReflectionProperty( Context::class, 'cache' );
		$prop->setAccessible( true );
		$prop->setValue( $ctx, [ 'cart_total' => $total ] );
		return $ctx;
	}

	public function test_gte_passes_when_equal(): void {
		$this->assertTrue( $this->cond->matches( $this->ctx( 500 ), [ 'op' => 'gte', 'amount' => 500 ] ) );
	}

	public function test_gte_passes_when_greater(): void {
		$this->assertTrue( $this->cond->matches( $this->ctx( 999 ), [ 'op' => 'gte', 'amount' => 500 ] ) );
	}

	public function test_gte_fails_when_less(): void {
		$this->assertFalse( $this->cond->matches( $this->ctx( 299 ), [ 'op' => 'gte', 'amount' => 300 ] ) );
	}

	public function test_lte_passes_when_less(): void {
		$this->assertTrue( $this->cond->matches( $this->ctx( 100 ), [ 'op' => 'lte', 'amount' => 200 ] ) );
	}

	public function test_lte_fails_when_greater(): void {
		$this->assertFalse( $this->cond->matches( $this->ctx( 500 ), [ 'op' => 'lte', 'amount' => 200 ] ) );
	}

	public function test_eq_passes_with_float_precision(): void {
		$this->assertTrue( $this->cond->matches( $this->ctx( 100.0 ), [ 'op' => 'eq', 'amount' => 100 ] ) );
	}

	public function test_eq_fails_on_mismatch(): void {
		$this->assertFalse( $this->cond->matches( $this->ctx( 100.5 ), [ 'op' => 'eq', 'amount' => 100 ] ) );
	}

	public function test_defaults_to_gte_on_unknown_op(): void {
		$this->assertTrue( $this->cond->matches( $this->ctx( 600 ), [ 'op' => 'unknown', 'amount' => 500 ] ) );
	}
}
