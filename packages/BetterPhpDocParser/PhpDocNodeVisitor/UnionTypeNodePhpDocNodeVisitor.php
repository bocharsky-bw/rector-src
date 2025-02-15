<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\PhpDocNodeVisitor;

use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use Rector\BetterPhpDocParser\Attributes\AttributeMirrorer;
use Rector\BetterPhpDocParser\Contract\BasePhpDocNodeVisitorInterface;
use Rector\BetterPhpDocParser\DataProvider\CurrentTokenIteratorProvider;
use Rector\BetterPhpDocParser\ValueObject\Parser\BetterTokenIterator;
use Rector\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Rector\BetterPhpDocParser\ValueObject\StartAndEnd;
use Rector\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode;
use Symplify\SimplePhpDocParser\PhpDocNodeVisitor\AbstractPhpDocNodeVisitor;

final class UnionTypeNodePhpDocNodeVisitor extends AbstractPhpDocNodeVisitor implements BasePhpDocNodeVisitorInterface
{
    public function __construct(
        private readonly CurrentTokenIteratorProvider $currentTokenIteratorProvider,
        private readonly AttributeMirrorer $attributeMirrorer
    ) {
    }

    public function enterNode(Node $node): ?Node
    {
        if (! $node instanceof UnionTypeNode) {
            return null;
        }

        if ($node instanceof BracketsAwareUnionTypeNode) {
            return null;
        }

        $startAndEnd = $this->resolveStardAndEnd($node);
        if (! $startAndEnd instanceof StartAndEnd) {
            return null;
        }

        $betterTokenProvider = $this->currentTokenIteratorProvider->provide();

        $isWrappedInCurlyBrackets = $this->isWrappedInCurlyBrackets($betterTokenProvider, $startAndEnd);
        $bracketsAwareUnionTypeNode = new BracketsAwareUnionTypeNode($node->types, $isWrappedInCurlyBrackets);

        $this->attributeMirrorer->mirror($node, $bracketsAwareUnionTypeNode);

        return $bracketsAwareUnionTypeNode;
    }

    private function isWrappedInCurlyBrackets(BetterTokenIterator $betterTokenProvider, StartAndEnd $startAndEnd): bool
    {
        $previousPosition = $startAndEnd->getStart() - 1;

        if ($betterTokenProvider->isTokenTypeOnPosition(Lexer::TOKEN_OPEN_PARENTHESES, $previousPosition)) {
            return true;
        }

        // there is no + 1, as end is right at the next token
        return $betterTokenProvider->isTokenTypeOnPosition(Lexer::TOKEN_CLOSE_PARENTHESES, $startAndEnd->getEnd());
    }

    private function resolveStardAndEnd(UnionTypeNode $unionTypeNode): ?StartAndEnd
    {
        $starAndEnd = $unionTypeNode->getAttribute(PhpDocAttributeKey::START_AND_END);
        if ($starAndEnd instanceof StartAndEnd) {
            return $starAndEnd;
        }

        // unwrap with parent array type...
        $parent = $unionTypeNode->getAttribute(PhpDocAttributeKey::PARENT);
        if (! $parent instanceof Node) {
            return null;
        }

        return $parent->getAttribute(PhpDocAttributeKey::START_AND_END);
    }
}
