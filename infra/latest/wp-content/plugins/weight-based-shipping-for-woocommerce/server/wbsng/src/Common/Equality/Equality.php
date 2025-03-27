<?php declare(strict_types=1);

namespace Gzp\WbsNg\Common\Equality;


/**
 * Do not try to split the interface because some of your objects are not asked for the hash. It might be that
 * today, but will likely change tomorrow. Equals and Hash usually required together. If you do not want to implement
 * the hash method just return 0 – it does not break the contract.
 */
interface Equality
{
    function equals($to): bool;

    /**
     * Implementation must return equal hashes for equal objects.
     * Implementations may (and likely will) return equal hashes for NOT equal objects. However, good hash distribution
     * allows better performance for callers ({@see Set}).
     */
    function hash(): int;
}