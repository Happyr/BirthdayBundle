<?php

namespace Happyr\BirthdayBundle\From\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class IncompleteDateTransformer
 *
 * @author Tobias Nyholm
 */
class BirthdayTransformer implements DataTransformerInterface
{
    /**
     * Do nothing when transforming from model -> norm
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


        return $date;
    }
}