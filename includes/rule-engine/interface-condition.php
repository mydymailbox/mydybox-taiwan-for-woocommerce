<?php
namespace Mydyma_TCS\Rule_Engine;

defined( 'ABSPATH' ) || exit;

interface Condition {
	public function id(): string;
	public function label(): string;
	public function type(): string; // 'select', 'number', 'text', etc.
	public function operators(): array;
	public function matches( Context $ctx, array $config ): bool;
}