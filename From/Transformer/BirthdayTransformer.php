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
    public function transform($date)
    {
        if ($date === null || !($date instanceof \DateTime)) {
            return null;
        }

        return array(
            'year' => $date->format('Y'),
            'month' => $date->format('n'),
            'day' => $date->format('j'),
        );
    }

    /**
     * @param mixed $data
     *
     * @return \DateTime|mixed
     */
    public function reverseTransform($data)
    {
        if ($data['year'] === null && $data['month'] === null && $data['day'] === null) {
            //if left untouched
            return null;
        } elseif ($data['year'] === null || $data['month'] === null || $data['day'] === null) {
            //if just partially filled in
            return 'incomplete';
        } elseif (!is_int($data['year']) || strlen($data['year']) != 4 || $data['year'] < 0) {
            return 'error';
        }

        $date = new \DateTime(sprintf('%d-%d-%d', $data['year'], $data['month'], $data['day']));

        return $date;
    }
}