<?php

declare(strict_types = 1);

/**
 * Class to iterator over a TestCollection
 *
 * @author Ryan Prather <godsgood33@gmail.com>
 */
final class TestIterator implements Iterator
{

    /**
     * Integer position
     *
     * @var int
     */
    private $position = 0;

    /**
     * Collection
     *
     * @var TestCollection
     */
    private $collection = null;

    /**
     * Constructor
     *
     * @param TestCollection $collection
     */
    public function __construct(TestCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Method to return the key position
     *
     * @return int
     *
     * {@inheritDoc}
     * @see Iterator::key()
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Method to return the current TestClass3
     *
     * @return TestClass3
     *
     * {@inheritDoc}
     * @see Iterator::current()
     */
    public function current(): TestClass3
    {
        return $this->collection->getData($this->position);
    }

    /**
     * Method to increment the index
     *
     * {@inheritDoc}
     * @see Iterator::next()
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * Method to reset the index
     *
     * @return void
     *
     * {@inheritDoc}
     * @see Iterator::rewind()
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Method to check if the current object has something in it
     *
     * @return bool
     *
     * {@inheritDoc}
     * @see Iterator::valid()
     */
    public function valid(): bool
    {
        return ! is_null($this->collection->getData($this->position));
    }
}
