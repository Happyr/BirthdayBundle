<?php

namespace Happyr\BirthdayBundle\From\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class IncompleteDateTransformer
 *
 * @author Tobias Nyholm
 */
class MonthAndDayTransformer implements DataTransformerInterface
{
    /**
     * Do nothing when transforming from norm -> view
     */
    public function transform($object)
    {
        return $object;
    }

    /**
     * If some components of the date is missing we'll add those.
     * This reverse transform will work when month and/or day is missing
     *
     */
    public function reverseTransform($date)
    {
        if (!is_array($date)) {
            return $date;
        }

        if (empty($date['year'])) {
            //2000 was a leap year
            $date['year'] = 2000;
        }

        return $date;
    }
}