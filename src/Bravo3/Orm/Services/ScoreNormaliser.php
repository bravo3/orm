<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Traits\ScorableInterface;

/**
 * Convert any given value to a numeric score
 */
class ScoreNormaliser
{
    const DEFAULT_SCORE = 0.0;

    /**
     * Return the score using the best scoring match for the given item
     *
     * @param mixed $item
     * @return float
     */
    public function score($item)
    {
        if (is_float($item) || is_int($item)) {
            return $this->scoreNumber($item);
        } elseif ($item instanceof \DateTime) {
            return $this->scoreDateTime($item);
        } elseif (is_object($item)) {
            return $this->scoreObject($item);
        } elseif (is_array($item)) {
            return $this->scoreArray($item);
        } elseif (is_string($item)) {
            return $this->scoreString($item);
        } else {
            return self::DEFAULT_SCORE;
        }
    }

    /**
     * Return the score of a string as a numeric representation
     *
     * @param string $item
     * @return float
     */
    public function scoreString($item)
    {
        $scored = 0;
        $score  = '';
        $index  = 0;
        $length = strlen($item);

        while ($scored < 7) {
            $c = $index < $length ? $this->getCharacterScore($item{$index++}) : '00';

            if ($c === null) {
                continue;
            }

            $score .= $c;

            if (++$scored == 4) {
                $score .= '.';
            }
        }

        return $this->scoreNumber($score);
    }

    /**
     * Get the score of a character as a 2-char string
     *
     * Returns null if the character is not a non-printable ASCII char
     *
     * @param string $c
     * @return string|null
     */
    protected function getCharacterScore($c)
    {
        $ord = ord($c);
        if ($ord < 32 || $ord > 126) {
            return null;
        } else {
            return str_pad((string)($ord - 31), 2, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Return the score of an array, by counting its elements
     *
     * @param array $item
     * @return float
     */
    public function scoreArray(array $item)
    {
        return (float)count($item);
    }

    /**
     * Return the score of an object
     *
     * @param object|ScorableInterface $item
     * @return float
     */
    public function scoreObject($item)
    {
        if ($item instanceof ScorableInterface) {
            return (float)$item->getScore();
        } else {
            return self::DEFAULT_SCORE;
        }
    }

    /**
     * Return the score of a DateTime object
     *
     * @param \DateTime $item
     * @return float
     */
    public function scoreDateTime(\DateTime $item)
    {
        return (float)$item->format('U');
    }

    /**
     * Return the score of a number
     *
     * @param int|float $item
     * @return float
     */
    public function scoreNumber($item)
    {
        return (float)$item;
    }
}
