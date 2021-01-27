<?php

declare(strict_types = 1);

/**
 * TestClass3 collection
 *
 * @author Ryan Prather <godsgood33@gmail.com>
 */
final class TestCollection implements IteratorAggregate
{

    /**
     * Variable to store the table data
     *
     * @var array
     */
    private $data = [];

    /**
     * Method to get the number of items in the collection
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Method to get an iterator for the collection
     *
     * @return TestIterator
     *
     * {@inheritDoc}
     * @see IteratorAggreate::getIterator()
     */
    public function getIterator(): TestIterator
    {
        return new TestIterator($this);
    }

    /**
     * Method to get data at an index
     *
     * @param int $position
     *
     * @return TestClass3|NULL
     */
    public function getData(int $position = 0)
    {
        if (isset($this->data[$position])) {
            return $this->data[$position];
        }

        return null;
    }

    /**
     * Method to add data to the collection
     *
     * @param TestClass3 $t
     *
     * @return TestCollection
     */
    public function addData(TestClass3 $t): TestCollection
    {
        $this->data[] = $t;

        return $this;
    }

    /**
     * Method to return the last element in the collection
     *
     * @return TestClass3
     */
    public function last(): TestClass3
    {
        return end($this->data);
    }
}
