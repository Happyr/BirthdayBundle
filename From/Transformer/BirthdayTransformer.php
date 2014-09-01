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
        if ($date === null) {
            return null;
        }

        return array(
            'year'=>$date->format('Y'),
            'date' =>$date
        );
    }

    /**
     * @param mixed $data
     *
     * @return \DateTime|mixed
     */
    public function reverseTransform($data)
    {
        $default = array(
            'year'=>null,
            'date'=>null,
        );

        $data = array_merge($default, $data);

        //error checks
        if (empty($data['year']) || !isset($data['date'])) {
            return 'error';
        }

        $date = new \DateTime($data['date']->format($data['year'].'-m-d'));

        return $date;
    }
}