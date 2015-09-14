<?php

namespace Application\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ParticipateType extends AbstractType
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Fix, so the url gets parsed thought twig
        $twig = clone $this->app['twig'];
        $twig->setLoader(new \Twig_Loader_String());

        $builder->add(
			$builder
				->create('participant', 'form', array(
						'by_reference' => true,
						'data_class' => 'Application\Entity\ParticipantEntity',
				))
				    ->add('name', 'text')
                    ->add('email', 'email')
		);

        $builder->add('public', 'checkbox', array(
            'label' => $twig->render(
                'You agree with our <a href="{{ url(\'application.terms\') }}" target="_blank">Terms</a>'
            ),
            'required' => true,
        ));

        $builder->add('Submit', 'submit', array(
            'attr' => array(
                'class' => 'btn-primary btn-lg btn-block',
            ),
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => true,
            'csrf_field_name' => 'csrf_token',
        ));
    }

    public function getName()
    {
        return 'participate';
    }
}
