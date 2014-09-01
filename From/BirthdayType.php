<?php

namespace Happyr\BirthdayBundle\From;

use Happyr\BirthdayBundle\From\Transformer\BirthdayTransformer;
use Happyr\BirthdayBundle\From\Transformer\MonthAndDayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\CallbackValidator;

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

        $builder->add('year', 'integer', array(
                'attr'=>array('placeholder'=>$options['empty_value']['year']),
            ));
        $builder->add(
            $builder->create('date', 'date', array(
                    'format'      => $options['format'],
                    'empty_value' => $options['empty_value'],
                ))
                ->addViewTransformer(new MonthAndDayTransformer())
        );

        $builder->addModelTransformer(new BirthdayTransformer());

        $this->addValidation($builder, $options);
    }

    protected function addValidation(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) use ($options) {
                $form = $event->getForm();
                $date = $form->get('date')->getData();
                $field = 'year';
                $year = $form->get($field)->getData();

                if (empty($year) xor empty($date)) {
                    $form[$field]->addError(new FormError('happyr.bithday.form.inclomplete'));
                }

                if (!empty($year)) {
                    $thisYear = date('Y');
                    $age = $thisYear - $year;
                    if ($age > $options['max_age']) {
                        $form[$field]->addError(new FormError('happyr.bithday.form.year.max_message'));
                    } else if ($age < $options['min_age']) {
                        $form[$field]->addError(new FormError('happyr.bithday.form.year.min_message'));
                    }
                }
            });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'max_age' => 120,
            'min_age' => 0,
            'format' => 'MMMMdd',
            'empty_value'=>array('year' => 'happyr.birthday.form.year', 'month' => 'happyr.birthday.form.month', 'day' => 'happyr.birthday.form.day')
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