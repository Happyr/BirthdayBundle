<?php

namespace Happyr\BirthdayBundle\From;

use Happyr\BirthdayBundle\From\Transformer\BirthdayTransformer;
use Happyr\BirthdayBundle\From\Transformer\MonthAndDayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class BirthdayType
 *
 * @author Tobias Nyholm
 */
class BirthdayType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //add 'y' to the format
        if (strstr($options['format'],'y') === false) {
            $options['format'].='y';
        }

        $builder->add('year', 'text');
        $builder->add(
            $builder->create('date', 'date', array(
                    'format'      => $options['format'],
                ))
                ->addViewTransformer(new MonthAndDayTransformer())
        );

        $builder->addModelTransformer(new BirthdayTransformer());
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'years' => range(date('Y') - 120, date('Y')),
            'format' => 'MMMMdd',
        ));
    }

    public function getParent()
    {
        return 'form';
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'happyr_birthday';
    }
}