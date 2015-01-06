<?php
namespace Bravo3\Orm\Traits;

interface ScorableInterface
{
    /**
     * Return the objects score
     *
     * @return float
     */
    public function getScore();
}
