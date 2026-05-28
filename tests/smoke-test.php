<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI test file, no HTTP output
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- standalone test runner globals
// phpcs:disable WordPress.PHP.DevelopmentFunctions -- var_export allowed in test assertions
// phpcs:disable PluginCheck.CodeAnalysis.NoDirectFileAccess -- this file bootstraps ABSPATH intentionally
/**
 * WC TW Core — Smoke Tests
 *
 * This file runs standalone tests for the Rule Engine to ensure core logic is correct.
 *
 * Execution:
 *   php tests/smoke-test.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

// WordPress helper stubs

// i18n stubs (no WordPress loaded in this standalone runner)
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) { return $text; }
}

// WP hook stubs (no-ops for standalone logic tests)
if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {}
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value, ...$args ) { return $value; }
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( ...$args ) { return true; }
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( ...$args ) { return true; }
}

if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	function wp_generate_uuid4(): string {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			random_int( 0, 0xffff ),
			random_int( 0, 0xffff ),
			random_int( 0, 0xffff ),
			random_int( 0, 0x0fff ) | 0x4000,
			random_int( 0, 0x3fff ) | 0x8000,
			random_int( 0, 0xffff ),
			random_int( 0, 0xffff ),
			random_int( 0, 0xffff )
		);
	}
}

// WC Logger stub
if ( ! class_exists( 'WC_Logger' ) ) {
	class WC_Logger {
		public function debug( string $msg, array $ctx = [] ): void {}
		public function info( string $msg, array $ctx = [] ): void {}
		public function warning( string $msg, array $ctx = [] ): void {}
		public function error( string $msg, array $ctx = [] ): void {}
	}
}

// --- Load Rule Engine --------------------------------------------------------

$base = __DIR__ . '/../includes/rule-engine/';

require_once $base . 'interface-condition.php';
require_once $base . 'interface-action.php';
require_once $base . 'class-context.php';
require_once $base . 'class-rule.php';
require_once $base . 'class-rule-engine.php';
require_once $base . 'conditions/class-cart-total.php';
require_once $base . 'conditions/class-max-qty.php';
require_once $base . 'conditions/class-address.php';
require_once $base . 'conditions/class-product.php';
require_once $base . 'conditions/class-category.php';
require_once $base . 'actions/class-hide-payment.php';
require_once $base . 'actions/class-hide-shipping.php';
require_once $base . 'actions/class-block-checkout.php';

use Taiwan_Store_Core\Rule_Engine\Rule_Engine;
use Taiwan_Store_Core\Rule_Engine\Rule;
use Taiwan_Store_Core\Rule_Engine\Context;

// --- Test Framework ----------------------------------------------------------

$tests_run    = 0;
$tests_passed = 0;
$tests_failed = 0;

function assert_true( string $test_name, bool $value ): void {
	global $tests_run, $tests_passed, $tests_failed;
	$tests_run++;
	if ( $value ) {
		$tests_passed++;
		echo "  \033[32mPASS\033[0m  {$test_name}\n";
	} else {
		$tests_failed++;
		echo "  \033[31mFAIL\033[0m  {$test_name}\n";
	}
}

function assert_false( string $test_name, bool $value ): void {
	assert_true( $test_name, ! $value );
}

function assert_equals( string $test_name, $expected, $actual ): void {
	global $tests_run, $tests_passed, $tests_failed;
	$tests_run++;
	if ( $expected === $actual ) {
		$tests_passed++;
		echo "  \033[32mPASS\033[0m  {$test_name}\n";
	} else {
		$tests_failed++;
		$e = var_export( $expected, true );
		$a = var_export( $actual, true );
		echo "  \033[31mFAIL\033[0m  {$test_name} (expected {$e}, got {$a})\n";
	}
}

function test_section( string $name ): void {
	echo "\n\033[1m{$name}\033[0m\n";
	echo str_repeat( '-', strlen( $name ) ) . "\n";
}

// --- Helpers -----------------------------------------------------------------

function make_context_with_total( float $total ): Context {
	$ctx = new Context();
	$r = new ReflectionProperty( Context::class, 'cache' );
	$r->setAccessible( true );
	$r->setValue( $ctx, [ 'cart_total' => $total ] );
	return $ctx;
}

function make_context_with_state( string $state ): Context {
	$ctx = new Context();
	$r   = new ReflectionProperty( Context::class, 'cache' );
	$r->setAccessible( true );
	$r->setValue( $ctx, [ 'shipping_country' => 'TW', 'shipping_state' => $state ] );
	return $ctx;
}

function make_context_with_products( array $product_ids ): Context {
	$ctx = new Context();
	$r   = new ReflectionProperty( Context::class, 'cache' );
	$r->setAccessible( true );
	$r->setValue( $ctx, [ 'product_ids' => $product_ids, 'category_ids' => [] ] );
	return $ctx;
}

// --- Setup Engine ------------------------------------------------------------

$engine = Rule_Engine::instance();
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Cart_Total() );
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Max_Qty() );
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Address() );
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Product() );
$engine->register_condition( new Taiwan_Store_Core\Rule_Engine\Conditions\Category() );
$engine->register_action( new Taiwan_Store_Core\Rule_Engine\Actions\Hide_Payment() );
$engine->register_action( new Taiwan_Store_Core\Rule_Engine\Actions\Hide_Shipping() );
$engine->register_action( new Taiwan_Store_Core\Rule_Engine\Actions\Block_Checkout() );

// --- T01: Cart_Total Condition -----------------------------------------------

test_section( 'T01: Cart_Total Condition' );

$cond = new Taiwan_Store_Core\Rule_Engine\Conditions\Cart_Total();

$ctx_500 = make_context_with_total( 500.0 );
$ctx_100 = make_context_with_total( 100.0 );
$ctx_100_exact = make_context_with_total( 100.0 );

assert_true(  'gte: 500 >= 500',  $cond->matches( $ctx_500, [ 'op' => 'gte', 'amount' => 500.0 ] ) );
assert_true(  'gte: 500 >= 100',  $cond->matches( $ctx_500, [ 'op' => 'gte', 'amount' => 100.0 ] ) );
assert_false( 'gte: 100 >= 500',  $cond->matches( $ctx_100, [ 'op' => 'gte', 'amount' => 500.0 ] ) );
assert_true(  'lte: 100 <= 500',  $cond->matches( $ctx_100, [ 'op' => 'lte', 'amount' => 500.0 ] ) );
assert_false( 'lte: 500 <= 100',  $cond->matches( $ctx_500, [ 'op' => 'lte', 'amount' => 100.0 ] ) );
assert_true(  'eq: 100 == 100',  $cond->matches( $ctx_100_exact, [ 'op' => 'eq', 'amount' => 100.0 ] ) );
assert_false( 'eq: 100 != 500',  $cond->matches( $ctx_100, [ 'op' => 'eq', 'amount' => 500.0 ] ) );

// --- T02: Address Condition --------------------------------------------------

test_section( 'T02: Address Condition' );

$addr_cond  = new Taiwan_Store_Core\Rule_Engine\Conditions\Address();
$ctx_taipei = make_context_with_state( 'TPE' );

assert_true(
	'state in [TPE, NWT]',
	$addr_cond->matches( $ctx_taipei, [ 'field' => 'state', 'op' => 'in', 'values' => [ 'TPE', 'NWT' ] ] )
);
assert_false(
	'state not in [TPE, NWT] should fail for TPE',
	$addr_cond->matches( $ctx_taipei, [ 'field' => 'state', 'op' => 'not_in', 'values' => [ 'TPE', 'NWT' ] ] )
);

// --- T03: Rule Engine evaluate: hide_payment ---------------------------------

test_section( 'T03: Rule_Engine evaluate: hide_payment when total >= 1000' );

$rule_data = [
	'id'         => 'test-rule-1',
	'name'       => 'Hide COD when total >= 1000',
	'hook'       => 'payment',
	'enabled'    => true,
	'conditions' => [
		[ 'type' => 'cart_total', 'config' => [ 'op' => 'gte', 'amount' => 1000.0 ] ],
	],
	'actions' => [
		[ 'type' => 'hide_payment', 'config' => [ 'gateways' => [ 'cod' ] ] ],
	],
];

// Inject rules directly into the engine's rules_cache (bypasses get_option).
// Rules are stored as plain arrays in the current engine API.
$rules_prop = new ReflectionProperty( Rule_Engine::class, 'rules_cache' );
$rules_prop->setAccessible( true );

$rules_prop->setValue( $engine, [
	'payment'  => [ $rule_data ],
	'shipping' => [],
	'cart'     => [],
] );

$ctx_high = make_context_with_total( 1500.0 );
$gateways = [ 'cod' => 'Cash on Delivery', 'bacs' => 'Bank Transfer' ];
$engine->evaluate( 'payment', $ctx_high, $gateways );
assert_false( 'cod is hidden (removed from payload)', array_key_exists( 'cod', $gateways ) );
assert_true(  'bacs remains (not hidden)', array_key_exists( 'bacs', $gateways ) );

$ctx_low = make_context_with_total( 500.0 );
$gateways2 = [ 'cod' => 'Cash on Delivery', 'bacs' => 'Bank Transfer' ];
$engine->evaluate( 'payment', $ctx_low, $gateways2 );
assert_true(  'cod remains when condition not met', array_key_exists( 'cod', $gateways2 ) );

// --- T04: Rule Engine evaluate: block_checkout -------------------------------

test_section( 'T04: Rule_Engine evaluate: block_checkout' );

$rule_block = [
	'id'         => 'test-rule-block',
	'name'       => 'Block checkout when total >= 5000',
	'hook'       => 'cart',
	'enabled'    => true,
	'conditions' => [
		[ 'type' => 'cart_total', 'config' => [ 'op' => 'gte', 'amount' => 5000.0 ] ],
	],
	'actions' => [
		[ 'type' => 'block_checkout', 'config' => [ 'message' => 'Order too large' ] ],
	],
];

$rules_prop->setValue( $engine, [
	'payment'  => [],
	'shipping' => [],
	'cart'     => [ $rule_block ],
] );

$ctx_high_cart = make_context_with_total( 6000.0 );
$payload = [ 'notices' => [] ];
$engine->evaluate( 'cart', $ctx_high_cart, $payload );
assert_true( 'block_checkout notice added', count( $payload['notices'] ) > 0 );
assert_true( 'notice message matches', strpos( $payload['notices'][0] ?? '', 'Order too large' ) !== false );

// --- T05: Short-circuit logic ------------------------------------------------

test_section( 'T05: Short-circuit when no rules' );

$rules_prop->setValue( $engine, [
	'payment'  => [],
	'shipping' => [],
	'cart'     => [],
] );

assert_false( 'payment has_rules returns false', $engine->has_rules( 'payment' ) );
assert_false( 'shipping has_rules returns false', $engine->has_rules( 'shipping' ) );
assert_false( 'cart has_rules returns false', $engine->has_rules( 'cart' ) );

// --- T06: Default Operators --------------------------------------------------

test_section( 'T06: Default op is gte' );

$cond_def = new Taiwan_Store_Core\Rule_Engine\Conditions\Cart_Total();
$ctx_200 = make_context_with_total( 200.0 );
assert_true(
	'No op defaults to gte: 200 >= 100',
	$cond_def->matches( $ctx_200, [ 'amount' => 100.0 ] )
);

// --- Final Results -----------------------------------------------------------

echo "\n" . str_repeat( '=', 50 ) . "\n";
echo "Results: {$tests_passed}/{$tests_run} Passed";
if ( $tests_failed > 0 ) {
	echo ", \033[31m{$tests_failed} Failed\033[0m";
} else {
	echo ", \033[32mAll Clear\033[0m";
}
echo "\n" . str_repeat( '=', 50 ) . "\n";

exit( $tests_failed > 0 ? 1 : 0 );
