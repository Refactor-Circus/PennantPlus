<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp\Tools;

use JayI\Foundation\Mcp\Tools\ListHistoryTool;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Named for the package key, which the class name would kebab-case to
 * `list-pennant-plus-history-tool`.
 */
#[Name('list-pennantplus-history-tool')]
final class ListPennantPlusHistoryTool extends ListHistoryTool {}
