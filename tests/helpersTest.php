<?php

namespace Spires;

class helpersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function head_returns_first_element_of_an_array()
    {
        $array = ['a', 'b', 'c', 'd'];
        $first = head($array);

        assertThat($first, is('a'));
    }

    /**
     * @test
     */
    public function tail_returns_array_without_first_element()
    {
        $array = ['a', 'b', 'c', 'd'];
        $tail = tail($array);

        assertThat($tail, is(['b', 'c', 'd']));
    }

}
