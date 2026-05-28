<?php
namespace Taiwan_Store_Core\Rule_Engine;

defined( 'ABSPATH' ) || exit;

interface Action {
	public function id(): string;
	public function label(): string;
	public function args(): array;
	public function execute( Context $ctx, array $config, array &$payload ): void;
}