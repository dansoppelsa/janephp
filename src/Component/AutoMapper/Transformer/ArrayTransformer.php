<?php

namespace Jane\Component\AutoMapper\Transformer;

use Jane\Component\AutoMapper\Extractor\PropertyMapping;
use Jane\Component\AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;

/**
 * Transformer array decorator.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final class ArrayTransformer implements TransformerInterface, DependentTransformerInterface
{
    private $itemTransformer;

    public function __construct(TransformerInterface $itemTransformer)
    {
        $this->itemTransformer = $itemTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
    {
        $valuesVar = new Expr\Variable($uniqueVariableScope->getUniqueName('values'));
        $statements = [
            // $values = [];
            new Stmt\Expression(new Expr\Assign($valuesVar, new Expr\Array_())),
        ];

        $loopValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('value'));

        [$output, $itemStatements] = $this->itemTransformer->transform($loopValueVar, $target, $propertyMapping, $uniqueVariableScope);

        if ($this->itemTransformer instanceof AssignedByReferenceTransformerInterface && $this->itemTransformer->assignByRef()) {
            $itemStatements[] = new Stmt\Expression(new Expr\AssignRef(new Expr\ArrayDimFetch($valuesVar), $output));
        } else {
            $itemStatements[] = new Stmt\Expression(new Expr\Assign(new Expr\ArrayDimFetch($valuesVar), $output));
        }

        $statements[] = new Stmt\Foreach_($input, $loopValueVar, [
            'stmts' => $itemStatements,
        ]);

        return [$valuesVar, $statements];
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return $this->itemTransformer->getDependencies();
    }
}
