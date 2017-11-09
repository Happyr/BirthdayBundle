<?php

namespace Happyr\BirthdayBundle\From;

use Happyr\BirthdayBundle\From\Transformer\BirthdayTransformer;
use Happyr\BirthdayBundle\From\Transformer\MonthAndDayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\CallbackValidator;

/**
 * Class BirthdayType
 *
 * @author Tobias Nyholm
 */
class BirthdayType extends AbstractType
{
    const DEFAULT_FORMAT = \IntlDateFormatter::LONG;

    private static $acceptedFormats = array(
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::MEDIUM,
        \IntlDateFormatter::SHORT,
    );

    /**
     * @var TranslatorInterface translator
     */
    private $translator;

    /**
     * BirthdayType constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $timeFormat = \IntlDateFormatter::NONE;
        $calendar = \IntlDateFormatter::GREGORIAN;

        $yearOptions = $monthOptions = $dayOptions = array(
            'error_bubbling' => true,
        );

        $formatter = new \IntlDateFormatter(
            \Locale::getDefault(),
            $options['format'],
            $timeFormat,
            'UTC',
            $calendar
        );
        $formatter->setLenient(false);


        $currentYear=date('Y');
        // Only pass a subset of the options to children
        $yearOptions['attr']['placeholder'] = $options['placeholder']['year'];
        $yearOptions['attr']['min'] = $currentYear-$options['max_age'];
        $yearOptions['attr']['max'] = $currentYear-$options['min_age'];

        $monthOptions['choices'] = $this->formatTimestamps($formatter, '/[M|L]+/', $this->listMonths($options['months']));
        $monthOptions['placeholder'] = $this->translator->trans($options['placeholder']['month']);
        $monthOptions['choice_translation_domain'] = false;

        $dayOptions['choices'] = $this->formatTimestamps($formatter, '/d+/', $this->listDays($options['days']));
        $dayOptions['placeholder'] = $this->translator->trans($options['placeholder']['day']);
        $dayOptions['choice_translation_domain'] = false;


        // Append generic carry-along options
        foreach (array('required', 'translation_domain') as $passOpt) {
            $yearOptions[$passOpt] = $monthOptions[$passOpt] = $dayOptions[$passOpt] = $options[$passOpt];
        }

        $this->doBuildForm($builder, $yearOptions, $monthOptions, $dayOptions, $formatter);
        $this->addValidation($builder, $options);
    }

    /**
     * Add validation
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    protected function addValidation(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) use ($options) {
            $form = $event->getForm();
            $date=$event->getData();

            if ($date === null) {
                return;
            } elseif ($date==='incomplete') {
                $form->addError(new FormError('happyr.birthday.form.incomplete'));
                $event->setData(null);
                return;
            } elseif ($date==='error') {
                $form->addError(new FormError('happyr.birthday.form.year.format_error'));
                $event->setData(null);
                return;
            } elseif ($date==='unknown_error') {
                $form->addError(new FormError('happyr.birthday.form.unknown_error'));
                $event->setData(null);
                return;
            }

            $yearField = $form->get('year');

            /*
             * Verify date
             */
            if ($date->format('Y') != $yearField->getData()) {
                $form->addError(new FormError('happyr.birthday.form.year.invalid'));
                return;
            }

            if ($date->format('n') != $form->get('month')->getData()) {
                $form->addError(new FormError('happyr.birthday.form.month.invalid'));
                return;
            }

            if ($date->format('j') != $form->get('day')->getData()) {
                $form->addError(new FormError('happyr.birthday.form.day.invalid'));
                return;
            }

            /*
             * Verify age
             */
            $now = new \DateTime();
            $age = $date->diff($now)->format('%r%y');

            if ($now < $date) {
                $form->addError(new FormError('happyr.birthday.form.future'));
            } else if ($age > $options['max_age']) {
                $form->addError(new FormError('happyr.birthday.form.year.max_message', null, array('%limit%'=>$options['max_age'])));
            } else if ($age < $options['min_age']) {
                $form->addError(new FormError('happyr.birthday.form.year.min_message', null, array('%limit%'=>$options['min_age'])));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'max_age' => 120,
            'min_age' => 0,
            'compound'=>true,
            'months'         => range(1, 12),
            'days'           => range(1, 31),
            'input'          => 'datetime',
            'placeholder'    => array(
                'year' => 'happyr.birthday.form.year',
                'month' => 'happyr.birthday.form.month',
                'day' => 'happyr.birthday.form.day'
            ),
            'format'         => self::DEFAULT_FORMAT,
            'error_bubbling' => false,
        ));
    }


    private function formatTimestamps(\IntlDateFormatter $formatter, $regex, array $timestamps)
    {
        $pattern = $formatter->getPattern();
        $timezone = $formatter->getTimezoneId();

        if ($setTimeZone = method_exists($formatter, 'setTimeZone')) {
            $formatter->setTimeZone('UTC');
        } else {
            $formatter->setTimeZoneId('UTC');
        }

        if (preg_match($regex, $pattern, $matches)) {
            $formatter->setPattern($matches[0]);

            foreach ($timestamps as $key => $timestamp) {
                $timestamps[$key] = $formatter->format($timestamp);
            }

            // I'd like to clone the formatter above, but then we get a
            // segmentation fault, so let's restore the old state instead
            $formatter->setPattern($pattern);
        }

        if ($setTimeZone) {
            $formatter->setTimeZone($timezone);
        } else {
            $formatter->setTimeZoneId($timezone);
        }

        return $timestamps;
    }

    private function listMonths(array $months)
    {
        $result = array();

        foreach ($months as $month) {
            $result[$month] = gmmktime(0, 0, 0, $month, 15);
        }

        return $result;
    }

    private function listDays(array $days)
    {
        $result = array();

        foreach ($days as $day) {
            $result[$day] = gmmktime(0, 0, 0, 5, $day);
        }

        return $result;
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

    /**
     * @param FormBuilderInterface $builder
     * @param $yearOptions
     * @param $monthOptions
     * @param $dayOptions
     * @param $formatter
     *
     * @return \Symfony\Component\Form\FormConfigBuilderInterface
     */
    protected function doBuildForm(FormBuilderInterface $builder, $yearOptions, $monthOptions, $dayOptions, $formatter)
    {
        return $builder
            ->add('year', IntegerType::class, $yearOptions)
            ->add('month', ChoiceType::class, $monthOptions)
            ->add('day', ChoiceType::class, $dayOptions)
            ->addViewTransformer(new BirthdayTransformer())
            ->setAttribute('formatter', $formatter);
    }
}